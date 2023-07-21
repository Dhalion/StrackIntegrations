<?php

declare(strict_types=1);

namespace StrackIntegrations\Service;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use StrackIntegrations\Struct\CustomerListPrice;
use StrackIntegrations\Struct\SalesPrice;

readonly class PriceTransformer
{
    public function __construct(
        private TaxCalculator $taxCalculator
    ) {
    }

    public function getProductCalculatedPrice(SalesPrice $customerPrice, SalesChannelProductEntity $product): CalculatedPrice
    {
        $startingQuantity = $product->getMinPurchase() ?: 1;
        $totalPrice = $customerPrice->getTotalPrice();

        $listPrice = null;
        if($customerPrice->getPercentageLineDiscount()) {
            $listPrice = new CustomerListPrice(
                $customerPrice->getUnitPrice() * $startingQuantity,
                $customerPrice->getUnitPrice() * $startingQuantity - $totalPrice,
                $customerPrice->getPercentageLineDiscount()
            );
        }

        $taxRules = $product->getCalculatedPrice()->getTaxRules();

        return new CalculatedPrice(
            $totalPrice / $startingQuantity,
            $totalPrice,
           $this->taxCalculator->calculateNetTaxes($totalPrice, $taxRules),
            $taxRules,
            (int)$customerPrice->getQuantity(),
            null,
            $listPrice
        );
    }
}
