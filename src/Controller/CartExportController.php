<?php declare(strict_types=1);

namespace StrackIntegrations\Controller;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Cart;
use avadim\FastExcelWriter\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Agiqon\SNProductCustomizer\Service\ProductCustomizationService;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\CartCalculator;

enum ExportType: string {
    case CSV = "csv";
    case XLSX = "xlsx";
}

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CartExportController extends StorefrontController {
    public function __construct(
        private readonly ?ProductCustomizationService $customizationService,
        private readonly CartCalculator $cartCalculator
    ) {
    }

    #[Route(
        path: "/strack-integrations/export-cart",
        name: "frontend.action.strack-integrations.export-cart",
        defaults: ['XmlHttpRequest' => 'true'],
        methods: ["GET"]
    )]
    public function exportCart(Request $request, SalesChannelContext $context, Cart $cart): StreamedResponse {
        $exportType = match($request->query->get('type')) {
            ExportType::XLSX->value => ExportType::XLSX,
            default => ExportType::CSV
        };
        $calculatedCart = $this->cartCalculator->calculate($cart, $context);
        $itemsList = $this->generateCartList($calculatedCart);

        // if no items in cart, return 204
        if (count($itemsList) === 0) {
            return new StreamedResponse(status: 204);
        }

        try {
            if ($exportType === ExportType::CSV) {
                return $this->createAndSendCSV($itemsList);
            } else {
                return $this->createAndSendXLSX($itemsList);
            }
        } catch (\Exception $e) {
            return new StreamedResponse(status: 500);
        }
    }

    private function generateCartList(Cart $cart): array {
        $list = [];
        $items = $cart->getLineItems();

        foreach ($items as $item) {
            $list[] = [
                'artikelnummer' => $item->getPayloadValue('productNumber') ?? null,
                'name' => $item->getLabel(),
                'bestellschluessel' => $this->generateOrderKey($item),
                'menge' => $item->getQuantity(),
                'einzelpreis' => $item->getPrice()->getUnitPrice(),
                'gesamtpreis' => $item->getPrice()->getTotalPrice(),
            ];
        }
        return $list;
    }

    private function createAndSendCSV(array $items): StreamedResponse {
        $response = new StreamedResponse(function () use ($items) {
            // Open the output stream in memory
            $output = fopen('php://output', 'w');

            fputcsv($output, $this->getTableHeaders(), ';');
            // Write the data to the output stream
            foreach ($items as $item) {
                fputcsv($output, $item, ';');
            }
            fclose($output);
        });

        // Set Response headers for an CSV file
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }


    private function createAndSendXLSX(array $items): StreamedResponse {
        $response = new StreamedResponse();

        // Set Response headers for an Excel file
        $response->headers->set("Content-Type", "application/vnd.ms-excel");
        $response->headers->set("Content-Disposition", 'attachment; filename="cart.xlsx"');

        $response->setCallback(function () use ($items) {
            // Open the output stream in memory
            $output = fopen("php://output", "w");

            $excel = Excel::create(["Cart"]);
            $sheet = $excel->sheet();

            $sheet->writeRow($this->getTableHeaders());
            foreach ($items as $item) {
                $sheet->writeRow($item);
            }

            // Write the Excel object to the output stream in memory
            fwrite($output, $excel->output() ?? "");
            fclose($output);
        });

        // Delete the Excel object to free memory
        unset($excel);

        return $response;
    }

    private function getTableHeaders(): array {
        return [
            'artikelnummer',
            'name',
            'bestellschluessel',
            'menge',
            'einzelpreis',
            'gesamtpreis'
        ];
    }

    private function generateOrderKey(LineItem $lineItem): string | null {
        // Check if customizationService (Dependency) is available
        if (!$this->customizationService) {
            return "Error: customizationService not available";
        }

        $purchaseId = $lineItem->getPayloadValue('customFields')['strack_bestellschluessel'] ?? null;
        $strackExtraLineItemInfo = $lineItem->getExtension('strackExtraLineItemInfo');

        if(!$purchaseId || !$strackExtraLineItemInfo) {
            return null;
        }

        $isProductCustomized = $lineItem->hasExtension('customization') && $this->customizationService->isProductCustomized(
                $lineItem->getExtension('customization'),
                $lineItem->getExtension('strackExtraLineItemInfo'));

        if($isProductCustomized) {
            $purchaseId = $this->customizationService->getCustomizationConvertedLineItem(
                $purchaseId,
                $lineItem->getExtension('customization'),
                $lineItem->getExtension('strackExtraLineItemInfo')
            );

            return $this->processDefaultGroupsForPuchaseKey($purchaseId, $lineItem->getExtension('strackExtraLineItemInfo'));
        }

        $purchaseId = null;
        foreach ($strackExtraLineItemInfo->get('options') as $option) {
            if ($option['groupName'] === 'Order No.') {
                $purchaseId = $option['name'];
                break;
            }
        }
        return $purchaseId;
    }

    private function processDefaultGroupsForPuchaseKey(string $purchaseKey, ArrayStruct $strackExtraLineItemInfo): string
    {
        foreach($strackExtraLineItemInfo->get('options') as $option) {
            $purchaseKey = str_replace('{' . $option['groupId'] . '}', $option['name'], $purchaseKey);
        }

        return $purchaseKey;
    }
}
