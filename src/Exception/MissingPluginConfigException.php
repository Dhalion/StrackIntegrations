<?php

declare(strict_types=1);

namespace StrackIntegrations\Exception;

class MissingPluginConfigException extends \Exception
{
    public function __construct(string $configKey)
    {
        parent::__construct(sprintf('Missing plugin config with key: %s.', $configKey));
    }
}
