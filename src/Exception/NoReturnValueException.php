<?php

declare(strict_types=1);

namespace StrackIntegrations\Exception;

class NoReturnValueException extends \Exception
{
    public function __construct(string $xmlContent)
    {
        parent::__construct(sprintf('In response XML there has been no return_value (JSON) provided. XML: %s', $xmlContent));
    }
}
