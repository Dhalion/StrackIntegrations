<?php declare(strict_types=1);

namespace StrackIntegrations\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use avadim\FastExcelWriter\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

enum ExportType: string {
    case CSV = "csv";
    case XLSX = "xlsx";
}

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CartExportController extends StorefrontController {

    public function __construct(
        private CartService $cartService,
        private EntityRepository $productRepository
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
        $list = $this->generateCartList($cart->getLineItems());

        // if no items in cart, return 204
        if (count($list) === 0) {
            return new StreamedResponse(status: 204);
        }

        try {
            if ($exportType === ExportType::CSV) {
                return $this->createAndSendCSV($list);
            } else {
                return $this->createAndSendXLSX($list);
            }
        } catch (\Exception $e) {
            return new StreamedResponse(status: 500);
        }
    }

    private function generateCartList(LineItemCollection $items): array {
        $list = [];
        foreach ($items as $item) {
            // Fetching custom fields from product adds ~10% to execution time
            $product = $this->getProductFromId($item->getReferencedId());
            $customFields = $product?->getCustomFields() ?? [];

            $list[] = [
                'artikelnummer' => $product->getProductNumber(),
                'name' => $item->getLabel(),
                'bestellschluessel' => $customFields['strack_bestellschluessel'] ?? null,
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

            fputcsv($output, $this->getTableHeaders());
            // Write the data to the output stream
            foreach ($items as $item) {
                fputcsv($output, $item);
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

    private function getProductFromId(string $id): ProductEntity | null {
        $criteria = new Criteria([$id]);
        return $this->productRepository->search($criteria, Context::createDefaultContext())->first() ?? null;
    }
}
