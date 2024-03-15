<?php

declare(strict_types=1);

namespace StrackIntegrations\Client;

use GuzzleHttp\Exception\BadResponseException;
use Symfony\Contracts\Cache\ItemInterface;

readonly class OrderClient extends AbstractClient
{
    public function getOrderList(
        string $customerNumber,
        string $documentType,
        ?string $orderFrom = null,
        ?string $orderTo = null,
    ): array {
        $contactNumber = $this->getContactNumber($customerNumber);
        if (!$contactNumber) {
            $exception = new \Exception(
                sprintf(
                    'contactNo not found for the customer: %s',
                    $customerNumber,
                ),
            );
            $this->logger->logException(self::class, $exception);
            return $this->buildErrorResponse($exception);
        }

        $endpoint = sprintf(
            '%s?$filter=sellToContactNo eq \'%s\' and documentType eq \'%s\'',
            $this->apiConfig->getOrderEndpoint(),
            $contactNumber,
            $documentType,
        );

        if ($orderFrom) {
            $endpoint .= sprintf(' and orderDate Ge %s', $orderFrom);
        }

        if ($orderTo) {
            $endpoint .= sprintf(' and orderDate Le %s', $orderTo);
        }

        try {
            $response = $this->get($endpoint);
        } catch (\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return $this->buildErrorResponse($exception);
        }

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    public function getOrderItems(string $orderNumber, string $documentType): array
    {
        try {
            $response = $this->get(
                sprintf(
                    '%s?$filter=documentNo eq \'%s\' and documentType eq \'%s\'',
                    $this->apiConfig->getOrderItemsEndpoint(),
                    $orderNumber,
                    $documentType,
                ),
            );
        } catch (\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return $this->buildErrorResponse($exception);
        }

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    public function getContactNumber(string $customerNumber): ?string
    {
        return $this->cache->get(
            sprintf('contact-response-%s', $customerNumber),
            function (ItemInterface $item) use ($customerNumber) {
                $item->expiresAfter(0);

                try {
                    $response = $this->get(
                        sprintf(
                            '%s?filter=bfnSWCode eq \'%s\'',
                            $this->apiConfig->getContactEndpoint(),
                            $customerNumber,
                        ),
                    );
                } catch (\Exception $exception) {
                    $this->logger->logException(self::class, $exception);
                    return null;
                }

                if (!isset($response[0]['bfnSWContactNo']) || !$response[0]['bfnSWContactNo']) {
                    $exception = new \Exception(
                        sprintf(
                            'bfnSWContactNo is not present in response: %s for customer: %s',
                            json_encode($response),
                            $customerNumber,
                        ),
                    );

                    $this->logger->logException(self::class, $exception);
                    return null;
                }

                $item->expiresAfter(86400);
                return $response[0]['bfnSWContactNo'];
            },
        );
    }

    private function buildErrorResponse(BadResponseException|\Exception $exception): array
    {
        $arrayException = [
            'success' => false,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'data' => [],
        ];

        if ($exception instanceof BadResponseException) {
            $arrayException['response'] = $exception->getResponse()->getBody()->getContents();
        }

        return $arrayException;
    }
}
