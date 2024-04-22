<?php

declare(strict_types=1);

namespace StrackIntegrations\Service;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use StrackIntegrations\Util\CustomFieldsInterface;

class CustomerErpService
{
    public static function isCustomerActive(?CustomerEntity $customer): bool
    {
        return (bool)$customer?->getCustomFieldsValue(CustomFieldsInterface::CUSTOMER_ERP_ACTIVE);
    }
}
