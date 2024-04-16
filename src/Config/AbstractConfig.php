<?php

declare(strict_types=1);

namespace StrackIntegrations\Config;

use Shopware\Core\System\SystemConfig\SystemConfigService;

abstract class AbstractConfig
{
    public const CONFIG_PREFIX = 'StrackIntegrations.config.';

    public function __construct(
        private readonly SystemConfigService $configService
    ) {}

    protected function getConfigValue(string $key, ?string $salesChannelId = null): array|int|float|bool|string|null
    {
        return $this->configService->get(self::CONFIG_PREFIX . $key, $salesChannelId);
    }

    protected function getConfigValueByFullKey(string $key, ?string $salesChannelId = null): array|int|float|bool|string|null
    {
        return $this->configService->get($key, $salesChannelId);
    }

}
