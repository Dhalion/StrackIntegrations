<?php

declare(strict_types=1);

namespace StrackIntegrations\Struct;

use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Framework\Util\FloatComparator;

class CustomerListPrice extends ListPrice
{
    public function __construct(float $price, float $discount, float $percentage)
    {
        $this->price = FloatComparator::cast($price);
        $this->discount = FloatComparator::cast($discount);
        $this->percentage = FloatComparator::cast($percentage);
    }
}
