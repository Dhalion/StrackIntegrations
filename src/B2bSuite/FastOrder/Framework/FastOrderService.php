<?php

declare(strict_types=1);

namespace StrackIntegrations\B2bSuite\FastOrder\Framework;

use Shopware\B2B\Common\File\CsvReader;
use Shopware\B2B\Common\File\XlsReader;
use Shopware\B2B\FastOrder\Framework\FastOrderContext;
use Shopware\B2B\LineItemList\Framework\LineItemShopWriterServiceInterface;
use Shopware\B2B\Shop\Framework\ProductServiceInterface;
use Shopware\B2B\StoreFrontAuthentication\Framework\OwnershipContext;
use Symfony\Component\HttpFoundation\File\File;

class FastOrderService extends \Shopware\B2B\FastOrder\Framework\FastOrderService
{
    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly XlsReader $xlsReader,
        private readonly ProductServiceInterface $productService,
        private readonly LineItemShopWriterServiceInterface $lineItemShopWriterService
    ) {
        parent::__construct($csvReader, $xlsReader, $productService, $lineItemShopWriterService);
    }

    public function processFastOrderFile(File $file, FastOrderContext $fastOrderContext, OwnershipContext $ownershipContext): array
    {
        $fastOrders = $this->createLineItemReferencesFromFileObject($file, $fastOrderContext, $ownershipContext);

        if (isset($fastOrders['error'])) {
            return $fastOrders;
        }

        $productOrderNumbers = array_keys($fastOrders);

        $matchingProductOrderNumbersPurchaseDetails = $this->productService
            ->fetchProductInformationByOrderNumbers($productOrderNumbers);

        $newProductOrderNumbers = [];
        foreach($productOrderNumbers as $productOrderNumber) {
            $search = array_filter($matchingProductOrderNumbersPurchaseDetails, function(array $row) use ($productOrderNumber) {
                return $row['strack_bestellschluessel_code'] === $productOrderNumber;
            });

            if (count($search) > 0) {
                $target = array_values($search)[0];
                $newProductOrderNumbers[$target['productNumber']] = $target['productNumber'];
            } else {
                $newProductOrderNumbers[$productOrderNumber] = $productOrderNumber;
            }
        }

        $productOrderNumbers = array_keys($newProductOrderNumbers);
        foreach($productOrderNumbers as $index => $productOrderNumber) {
            if ($productOrderNumber) {
                $productOrderNumbers[$index] = (string)$productOrderNumber;
            }
        }

        $newFastOrders = [];
        foreach($fastOrders as $fastOrder) {
            $search = array_filter($matchingProductOrderNumbersPurchaseDetails, function(array $row) use ($fastOrder) {
                return $row['strack_bestellschluessel_code'] === $fastOrder->referenceNumber;
            });

            if (count($search) > 0) {
                $target = array_values($search)[0];
                $fastOrder->referenceNumber = $target['productNumber'];
                $newFastOrders[$target['productNumber']] = $fastOrder;
            } else {
                $newFastOrders[$fastOrder->referenceNumber] = $fastOrder;
            }
        }

        $fastOrders = $newFastOrders;

        $notMatchingProducts = array_diff(
            $productOrderNumbers,
            array_keys($matchingProductOrderNumbersPurchaseDetails)
        );

        $products = [];
        foreach ($productOrderNumbers as $productOrderNumber) {
            if (!array_key_exists($productOrderNumber, $matchingProductOrderNumbersPurchaseDetails)) {
                continue;
            }

            $lineItemReference = $fastOrders[$productOrderNumber];
            $lineItemReference->name = $matchingProductOrderNumbersPurchaseDetails[$productOrderNumber]['name'];
            $lineItemReference->minPurchase = $matchingProductOrderNumbersPurchaseDetails[$productOrderNumber]['minPurchase'];
            $lineItemReference->maxPurchase = $matchingProductOrderNumbersPurchaseDetails[$productOrderNumber]['maxPurchase'];
            $lineItemReference->purchaseStep = $matchingProductOrderNumbersPurchaseDetails[$productOrderNumber]['purchaseStep'];

            $products[] = $lineItemReference;
        }

        return [
            'matchingProducts' => $products,
            'notMatchingProducts' => $notMatchingProducts,
        ];
    }

}
