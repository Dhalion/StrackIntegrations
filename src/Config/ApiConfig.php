<?php

declare(strict_types=1);

namespace StrackIntegrations\Config;

class ApiConfig extends AbstractConfig
{
    public function getClientId(): string
    {
        return (string)$this->getConfigValue('clientId');
    }

    public function getClientSecret(): string
    {
        return (string)$this->getConfigValue('clientSecret');
    }

    public function getAccessTokenUri(): string
    {
        return $this->removeTrailingSlash((string)$this->getConfigValue('accessTokenUri'));
    }

    public function getScope(): string
    {
        return $this->removeTrailingSlash((string)$this->getConfigValue('scope'));
    }

    public function getApiDomain(): string
    {
        return $this->removeTrailingSlash((string)$this->getConfigValue('apiDomain'));
    }

    public function getPriceEndpoint(): string
    {
        return $this->addStartingSlash((string)$this->getConfigValue('priceEndpoint'));
    }

    private function removeTrailingSlash(string $url): string
    {
        return rtrim($url, '/');
    }

    private function addStartingSlash(string $path): string
    {
        if(str_starts_with('/', $path) || !$path) {
            return $path;
        }

        return '/' . $path;
    }
}
