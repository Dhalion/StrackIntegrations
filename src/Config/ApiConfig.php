<?php

declare(strict_types=1);

namespace StrackIntegrations\Config;

use StrackIntegrations\Exception\MissingPluginConfigException;

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

    public function getPriceSoapAction(): string
    {
        return (string)$this->getConfigValue('priceSoapAction');
    }

    public function isTestModeOn(): bool
    {
        return (bool)$this->getConfigValue('testModeOn');
    }

    public function getTestModeDebtorNumber(): string
    {
        return (string)$this->getConfigValue('testModeDebtorNumber');
    }

    public function getContactEndpoint(): string
    {
        return (string)$this->getConfigValue('contactEndpoint');
    }

    public function getOrderEndpoint(): string
    {
        return (string)$this->getConfigValue('orderEndpoint');
    }

    public function getOrderItemsEndpoint(): string
    {
        return (string)$this->getConfigValue('orderItemsEndpoint');
    }

    public function getOrderTestModeOn(): bool
    {
        return (bool)$this->getConfigValue('orderTestModeOn');
    }

    public function getOrderTestCustomerNumber(): string
    {
        return (string)$this->getConfigValue('orderTestCustomerNumber');
    }

    public function validateConfig(): void
    {
        if(!$this->getClientId()) {
            throw new MissingPluginConfigException('clientId');
        }

        if(!$this->getClientSecret()) {
            throw new MissingPluginConfigException('clientSecret');
        }

        if(!$this->getAccessTokenUri()) {
            throw new MissingPluginConfigException('accessTokenUri');
        }

        if(!$this->getScope()) {
            throw new MissingPluginConfigException('scope');
        }

        if(!$this->getApiDomain()) {
            throw new MissingPluginConfigException('apiDomain');
        }

        if(!$this->getPriceEndpoint()) {
            throw new MissingPluginConfigException('priceEndpoint');
        }

        if(!$this->getPriceSoapAction()) {
            throw new MissingPluginConfigException('priceSoapAction');
        }

        if(!$this->getContactEndpoint()) {
            throw new MissingPluginConfigException('contactEndpoint');
        }

        if(!$this->getOrderEndpoint()) {
            throw new MissingPluginConfigException('orderEndpoint');
        }

        if(!$this->getOrderItemsEndpoint()) {
            throw new MissingPluginConfigException('orderItemsEndpoint');
        }
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

    private function isUrlValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

}
