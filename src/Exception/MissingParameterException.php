<?php

declare(strict_types=1);

namespace StrackIntegrations\Exception;

class MissingParameterException extends \Exception
{
    public function __construct(string $parameterName, ?string $xmlContent = null)
    {
        if($xmlContent) {
            parent::__construct(sprintf('Missing response parameter: %s. XML: %s', $parameterName, $xmlContent));
        } else {
            parent::__construct(sprintf('Missing response parameter: %s.', $parameterName));
        }
    }
}
