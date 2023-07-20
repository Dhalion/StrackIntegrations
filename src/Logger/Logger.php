<?php

namespace StrackIntegrations\Logger;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Monolog\Logger as MonologLogger;

class Logger
{
    public function __construct(
        protected MonologLogger $logger
    ){}

    public function logException(Exception $exception, array $additionalData = []): void
    {
        $exceptionArray = $this->buildExceptionMessage($exception, $additionalData);

        if($exception instanceof BadResponseException) {
            $exceptionArray = array_merge(
                $exceptionArray,
                $this->buildGuzzleException($exception)
            );
        }

        $this->logger->error(json_encode($exceptionArray));
    }

    private function buildExceptionMessage(Exception $exception, array $additionalData): array
    {
        $exceptionArray = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString()
        ];

        if($additionalData) {
            $exceptionArray = array_merge($exceptionArray, [
                'payload' => $additionalData
            ]);
        }

        return $exceptionArray;
    }

    private function buildGuzzleException(BadResponseException $exception): array
    {
        return [
            'requestUri' => $exception->getRequest()->getUri()->getPath(),
            'requestContent' => $exception->getRequest()->getBody()->getContents(),
            'requestMethod' => $exception->getRequest()->getMethod(),
            'responseContent' => $exception->getResponse()->getBody()->getContents(),
            'responseCode' => $exception->getResponse()->getStatusCode()
        ];
    }
}
