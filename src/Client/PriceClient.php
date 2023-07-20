<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use StrackIntegrations\Exception\MissingParameterException;
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

        $this->validateResponse($jsonResponse, $response->asXML());

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

    private function validateResponse(array $response, string $xmlResponse): void
    {
        if (!array_key_exists('No.', $response)) {
            throw new MissingParameterException('No.', $xmlResponse);
        }

        if (!array_key_exists('Sell-to Customer No.', $response)) {
            throw new MissingParameterException('Sell-to Customer No.', $xmlResponse);
        }

        if (!array_key_exists('Unit Price', $response)) {
            throw new MissingParameterException('Unit Price', $xmlResponse);
        }

        if (!array_key_exists('Quantity', $response)) {
            throw new MissingParameterException('Quantity', $xmlResponse);
        }

        if (!array_key_exists('Line Discount %', $response)) {
            throw new MissingParameterException('Line Discount %', $xmlResponse);
        }

        if (!array_key_exists('Line Amount', $response)) {
            throw new MissingParameterException('Line Amount', $xmlResponse);
        }

        if (!array_key_exists('Amount Including VAT', $response)) {
            throw new MissingParameterException('Amount Including VAT', $xmlResponse);
        }

        if (!array_key_exists('Prices Including VAT', $response)) {
            throw new MissingParameterException('Prices Including VAT', $xmlResponse);
        }

        if (!array_key_exists('Currency Code', $response)) {
            throw new MissingParameterException('Currency Code', $xmlResponse);
        }
    }
}
