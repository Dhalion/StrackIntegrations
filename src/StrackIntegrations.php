<?php

declare(strict_types=1);

namespace StrackIntegrations;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use StrackIntegrations\Installer\CustomerCustomFieldsInstaller;

class StrackIntegrations extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        (new CustomerCustomFieldsInstaller($this->container))->installCustomFieldSet($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if($uninstallContext->keepUserData()) {
            return;
        }

        (new CustomerCustomFieldsInstaller($this->container))->uninstallCustomFieldSet($uninstallContext);
    }
}
