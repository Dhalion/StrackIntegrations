<?php

declare(strict_types=1);

namespace StrackIntegrations\Service;

use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use StrackIntegrations\Struct\CustomerListPrice;
use StrackIntegrations\Struct\LiveCalculatedPrice;
use StrackIntegrations\Struct\SalesPrice;
use StrackIntegrations\Struct\SalesPriceCollection;

readonly class PriceTransformer
{
    public function __construct(
        private TaxCalculator $taxCalculator
    ) {
    }

    public function setCalculatedPrices(SalesPriceCollection $salesPrices, SalesChannelProductCollection $products): void
    {
        foreach($products as $product) {
            $salesPrice = $salesPrices->filter(function(SalesPrice $salesPrice) use($product) {return $salesPrice->getProductNumber() === $product->getProductNumber();})->first();
            if(!$salesPrice) {
                continue;
            }

            $this->setCalculatedPrice($salesPrice, $product);
        }
    }

    public function setCalculatedPrice(SalesPrice $customerPrice, SalesChannelProductEntity $product): void
    {
        $calculatedPrice = $this->getCalculatedPrice(
            $customerPrice,
            $product->getMinPurchase() ?: 1,
            $product->getCalculatedPrice()->getTaxRules()
        );

        $product->setCalculatedPrice($calculatedPrice);
        $product->setCalculatedPrices(new PriceCollection([$calculatedPrice]));
    }

    public function getCalculatedPrice(SalesPrice $customerPrice, int $quantity, TaxRuleCollection $taxRules, bool $displayTotalPrice = false): LiveCalculatedPrice
    {
        $totalPrice = $customerPrice->getTotalPrice();

        $listPrice = null;
        if($customerPrice->getPercentageLineDiscount()) {
            $listPrice = new CustomerListPrice(
                $customerPrice->getUnitPrice() * $quantity,
                $customerPrice->getUnitPrice() * $quantity - $totalPrice,
                $customerPrice->getPercentageLineDiscount()
            );
        }

        $calculatedPrice = new LiveCalculatedPrice(
            $displayTotalPrice ? $totalPrice : $totalPrice / $quantity,
            $totalPrice,
            $this->taxCalculator->calculateNetTaxes($totalPrice, $taxRules),
            $taxRules,
            (int)$customerPrice->getQuantity(),
            null,
            $listPrice
        );

        $calculatedPrice->setHasError($customerPrice->hasError());
        return $calculatedPrice;
    }
}
