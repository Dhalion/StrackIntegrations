<?php

declare(strict_types=1);

namespace StrackIntegrations\Struct;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

class LiveCalculatedPrice extends CalculatedPrice
{
    private bool $hasError = false;

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function setHasError(bool $hasError): LiveCalculatedPrice
    {
        $this->hasError = $hasError;
        return $this;
    }
}
