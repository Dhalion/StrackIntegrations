<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use StrackIntegrations\Struct\SalesPrice;

readonly class PriceClient extends AbstractClient
{
    public function getSalesPrice(string $debtorNumber, string $productNumber, int $quantity): ?SalesPrice
    {
        $response = $this->post($this->apiConfig->getPriceEndpoint(), $this->apiConfig->getPriceSoapAction(), [
            'customerId' => $debtorNumber,
            'itemNo' => $productNumber,
            'quantity' => $quantity
        ]);

        $this->validateResponse($response);

        return (new SalesPrice())
            ->setProductNumber($response['No.'])
            ->setDebtorNumber($response['Sell-to Customer No.'])
            ->setUnitPrice($response['Unit Price'])
            ->setQuantity($response['Quantity'])
            ->setPercentageLineDiscount($response['Line Discount %'])
            ->setTotalPrice($response['LineAmount'])
            ->setTotalPriceWithVat($response['AmountIncluding VAT'])
            ->setIsBrutto($response['Prices Including VAT'])
            ->setCurrencyIso($response['Currency Code']);
    }

    private function validateResponse(array $response): void
    {
        //todo
    }
}
