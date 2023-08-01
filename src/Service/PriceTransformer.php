<?php

declare(strict_types=1);

namespace StrackIntegrations\Service;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use StrackIntegrations\Struct\CustomerListPrice;
use StrackIntegrations\Struct\SalesPrice;

readonly class PriceTransformer
{
    public function __construct(
        private TaxCalculator $taxCalculator
    ) {
    }

    public function getCalculatedPrice(SalesPrice $customerPrice, int $quantity, TaxRuleCollection $taxRules, bool $displayTotalPrice = false): CalculatedPrice
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

        return new CalculatedPrice(
            $displayTotalPrice ? $totalPrice : $totalPrice / $quantity,
            $totalPrice,
           $this->taxCalculator->calculateNetTaxes($totalPrice, $taxRules),
            $taxRules,
            (int)$customerPrice->getQuantity(),
            null,
            $listPrice
        );
    }
}
