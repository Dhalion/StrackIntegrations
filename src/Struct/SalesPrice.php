<?php

declare(strict_types=1);

namespace StrackIntegrations\Struct;

use Shopware\Core\Framework\Struct\Struct;

class SalesPrice extends Struct
{
    private string $productNumber;
    private float $unitPrice;
    private float $quantity;
    private float $percentageLineDiscount;
    private float $totalPrice;
    private float $totalPriceWithVat;
    private string $debtorNumber;
    private string $currencyIso;
    private bool $isBrutto;
    private bool $hasError;

    public function getProductNumber(): string
    {
        return $this->productNumber;
    }

    public function setProductNumber(string $productNumber): SalesPrice
    {
        $this->productNumber = $productNumber;
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): SalesPrice
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): SalesPrice
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPercentageLineDiscount(): float
    {
        return $this->percentageLineDiscount;
    }

    public function setPercentageLineDiscount(float $percentageLineDiscount): SalesPrice
    {
        $this->percentageLineDiscount = $percentageLineDiscount;
        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): SalesPrice
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getTotalPriceWithVat(): float
    {
        return $this->totalPriceWithVat;
    }

    public function setTotalPriceWithVat(float $totalPriceWithVat): SalesPrice
    {
        $this->totalPriceWithVat = $totalPriceWithVat;
        return $this;
    }

    public function getDebtorNumber(): string
    {
        return $this->debtorNumber;
    }

    public function setDebtorNumber(string $debtorNumber): SalesPrice
    {
        $this->debtorNumber = $debtorNumber;
        return $this;
    }

    public function getCurrencyIso(): string
    {
        return $this->currencyIso;
    }

    public function setCurrencyIso(string $currencyIso): SalesPrice
    {
        $this->currencyIso = $currencyIso;
        return $this;
    }

    public function isBrutto(): bool
    {
        return $this->isBrutto;
    }

    public function setIsBrutto(bool $isBrutto): SalesPrice
    {
        $this->isBrutto = $isBrutto;
        return $this;
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function setHasError(bool $hasError): SalesPrice
    {
        $this->hasError = $hasError;
        return $this;
    }

    public static function createErrorSalesPrice(string $productNumber): SalesPrice
    {
        return (new self())
            ->setProductNumber($productNumber)
            ->setUnitPrice(0.0)
            ->setQuantity(0.0)
            ->setPercentageLineDiscount(0.0)
            ->setTotalPrice(0.0)
            ->setDebtorNumber('')
            ->setCurrencyIso('')
            ->setIsBrutto(false)
            ->setHasError(true);
    }
}
