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

        $listPrice = null;
        if($customerPrice->getPercentageLineDiscount()) {
            $listPrice = new CustomerListPrice(
                $customerPrice->getUnitPrice() * $startingQuantity,
                $customerPrice->getUnitPrice() * $startingQuantity - $customerPrice->getTotalPrice(),
                $customerPrice->getPercentageLineDiscount()
            );
        }

        return new CalculatedPrice(
            $customerPrice->getTotalPrice(),
            $customerPrice->getTotalPrice(),
            $product->getCalculatedPrice()->getCalculatedTaxes(), //todo
            $product->getCalculatedPrice()->getTaxRules(),
            (int)$customerPrice->getQuantity(),
            null,
            $listPrice
        );
    }
}
