<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use StrackIntegrations\Exception\NoReturnValueException;
use StrackIntegrations\Struct\SalesPrice;

readonly class PriceClient extends AbstractClient
{
    public function getSalesPrice(string $debtorNumber, string $productNumber, int $quantity): ?SalesPrice
    {
        $response = $this->post(
            $this->apiConfig->getPriceEndpoint(),
            $this->apiConfig->getPriceSoapAction(),
            $this->getSalesPriceEnvelope(),
            [
                'customerId' => $debtorNumber,
                'itemNo' => $productNumber,
                'quantity' => $quantity
            ]
        );

        $namespaces = $response->getNamespaces(true);
        $swWebServices = $response->children($namespaces['Soap'])->Body->children($namespaces['']);

        if (!isset($swWebServices->GetSalesPrice_Result->return_value)) {
            throw new NoReturnValueException($response->asXML());
        }

        $returnValue = (string)$swWebServices->GetSalesPrice_Result->return_value;
        $jsonResponse = json_decode($returnValue, true, 512, JSON_THROW_ON_ERROR);

        $this->validateResponse($jsonResponse);

        return (new SalesPrice())
            ->setProductNumber($jsonResponse['No.'])
            ->setDebtorNumber($jsonResponse['Sell-to Customer No.'])
            ->setUnitPrice($jsonResponse['Unit Price'])
            ->setQuantity($jsonResponse['Quantity'])
            ->setPercentageLineDiscount($jsonResponse['Line Discount %'])
            ->setTotalPrice($jsonResponse['Line Amount'])
            ->setTotalPriceWithVat($jsonResponse['Amount Including VAT'])
            ->setIsBrutto($jsonResponse['Prices Including VAT'])
            ->setCurrencyIso($jsonResponse['Currency Code']);
    }

    private function getSalesPriceEnvelope(): string
    {
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:lic="urn:microsoft-dynamics-schemas/codeunit/SWWebServices">
    <soapenv:Header></soapenv:Header>
    <soapenv:Body>
        <lic:GetSalesPrice>
            <lic:parameter>%%%template_json%%%</lic:parameter>
        </lic:GetSalesPrice>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function validateResponse(array $response): void
    {
        //todo
    }
}
