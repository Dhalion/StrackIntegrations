<?php

namespace StrackIntegrations\Logger;

use Exception;
use Monolog\Logger as MonologLogger;

class Logger
{
    public function __construct(
        protected MonologLogger $logger
    ){}

    public function logException(Exception $exception, array $additionalData = []): void
    {
        $this->logger->error(
            json_encode(
                $this->buildExceptionMessage($exception, $additionalData)
            )
        );
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
}
