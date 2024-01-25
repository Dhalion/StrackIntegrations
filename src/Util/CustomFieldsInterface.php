<?php

declare(strict_types=1);

namespace StrackIntegrations\Util;

interface CustomFieldsInterface
{
    public const CUSTOMER_CUSTOM_FIELD_SET = 'strack_integrations_customer';
    public const CUSTOMER_MINIMUM_ORDER_VALUE = 'strack_customer_minimum_order_value';
    public const ORDER_CUSTOM_FIELD_SET = 'strack_integrations_order';
    public const ORDER_IS_OFFER = 'strack_order_has_price_error';
}
