<?php

declare(strict_types=1);

namespace StrackIntegrations;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use StrackIntegrations\Installer\CustomerCustomFieldsInstaller;
use StrackIntegrations\Installer\OrderCustomFieldsInstaller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class StrackIntegrations extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = \rtrim($this->getPath(), '/') . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }

    public function install(InstallContext $installContext): void
    {
        (new CustomerCustomFieldsInstaller($this->container))->installCustomFieldSet($installContext->getContext());
        (new OrderCustomFieldsInstaller($this->container))->installCustomFieldSet($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        (new CustomerCustomFieldsInstaller($this->container))->installCustomFieldSet($updateContext->getContext());
        (new OrderCustomFieldsInstaller($this->container))->installCustomFieldSet($updateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if($uninstallContext->keepUserData()) {
            return;
        }

        (new CustomerCustomFieldsInstaller($this->container))->uninstallCustomFieldSet($uninstallContext->getContext());
        (new OrderCustomFieldsInstaller($this->container))->uninstallCustomFieldSet($uninstallContext->getContext());
    }
}
