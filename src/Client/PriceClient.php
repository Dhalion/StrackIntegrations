<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use StrackIntegrations\Exception\MissingParameterException;
use StrackIntegrations\Exception\NoReturnValueException;
use StrackIntegrations\Struct\SalesPrice;
use StrackIntegrations\Struct\SalesPriceCollection;

readonly class PriceClient extends AbstractClient
{
    /**
     * @param array<string, int> $productNumbers Key: product number, value: quantity
     */
    public function getSalesPrices(string $debtorNumber, array $productNumbers, string $currencyIso): SalesPriceCollection
    {
        $requestData = [];

        foreach($productNumbers as $productNumber => $quantity) {
            $requestData[] = [
               'customerId' => $debtorNumber,
               'itemNo' => (string)$productNumber,
               'currencyIso' => $currencyIso,
               'quantity' => $quantity
            ];
        }

        try {
            $response = $this->post(
                $this->apiConfig->getPriceEndpoint(),
                $this->apiConfig->getPriceSoapAction(),
                $this->getSalesPriceEnvelope(),
                $requestData
            );
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            $salesPriceCollection = new SalesPriceCollection();
            foreach($productNumbers as $productNumber => $quantity) {
                $salesPriceCollection->add(SalesPrice::createErrorSalesPrice((string)$productNumber));
            }

            return $salesPriceCollection;
        }

        $namespaces = $response->getNamespaces(true);
        $swWebServices = $response->children($namespaces['Soap'])->Body->children($namespaces['']);

        if (!isset($swWebServices->GetSalesPrice_Result->return_value)) {
            $exception = new NoReturnValueException($response->asXML());
            $this->logger->logException(self::class, $exception);
            throw $exception;
        }

        $returnValue = (string)$swWebServices->GetSalesPrice_Result->return_value;
        $jsonResponse = json_decode($returnValue, true, 512, JSON_THROW_ON_ERROR);

        if(empty($jsonResponse)) {
            return new SalesPriceCollection();
        }

        $salesPriceCollection = new SalesPriceCollection();

        foreach($jsonResponse as $product) {
            try {
                $this->validateResponse($product, $response->asXML());
            } catch(MissingParameterException) {
                if(isset($product['No.'])) {
                    $salesPriceCollection->add(SalesPrice::createErrorSalesPrice($product['No.']));
                }

                continue;
            }

            $salesPrice = (new SalesPrice())
                ->setProductNumber($product['No.'])
                ->setDebtorNumber($product['Sell-to Customer No.'])
                ->setUnitPrice($product['Unit Price'])
                ->setQuantity($product['Quantity'])
                ->setPercentageLineDiscount($product['Line Discount %'])
                ->setTotalPrice($product['Line Amount'])
                ->setTotalPriceWithVat($product['Amount Including VAT'])
                ->setIsBrutto($product['Prices Including VAT'])
                ->setCurrencyIso($product['Currency Code'])
                ->setHasError($product['Unit Price'] <= 0);

            $salesPriceCollection->add($salesPrice);
        }

        return $salesPriceCollection;
    }

    public function getSalesPrice(string $debtorNumber, string $productNumber, string $currencyIso, int $quantity = 1): ?SalesPrice
    {
        try {
            $response = $this->post(
                $this->apiConfig->getPriceEndpoint(),
                $this->apiConfig->getPriceSoapAction(),
                $this->getSalesPriceEnvelope(),
                [
                    'customerId' => $debtorNumber,
                    'itemNo' => $productNumber,
                    'currencyIso' => $currencyIso,
                    'quantity' => $quantity
                ]
            );
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return SalesPrice::createErrorSalesPrice($productNumber);
        }


        $namespaces = $response->getNamespaces(true);
        $swWebServices = $response->children($namespaces['Soap'])->Body->children($namespaces['']);

        if (!isset($swWebServices->GetSalesPrice_Result->return_value)) {
            $exception = new NoReturnValueException($response->asXML());
            $this->logger->logException(self::class, $exception);
            throw $exception;
        }

        $returnValue = (string)$swWebServices->GetSalesPrice_Result->return_value;
        $jsonResponse = json_decode($returnValue, true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->validateResponse($jsonResponse, $response->asXML());
        } catch(MissingParameterException $exception) {
            if (isset($jsonResponse['No.'])) {
                return SalesPrice::createErrorSalesPrice($jsonResponse['No.']);
            } else {
                throw $exception;
            }
        }

        return (new SalesPrice())
            ->setProductNumber($jsonResponse['No.'])
            ->setDebtorNumber($jsonResponse['Sell-to Customer No.'])
            ->setUnitPrice($jsonResponse['Unit Price'])
            ->setQuantity($jsonResponse['Quantity'])
            ->setPercentageLineDiscount($jsonResponse['Line Discount %'])
            ->setTotalPrice($jsonResponse['Line Amount'])
            ->setTotalPriceWithVat($jsonResponse['Amount Including VAT'])
            ->setIsBrutto($jsonResponse['Prices Including VAT'])
            ->setCurrencyIso($jsonResponse['Currency Code'])
            ->setHasError($jsonResponse['Unit Price'] <= 0);
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
