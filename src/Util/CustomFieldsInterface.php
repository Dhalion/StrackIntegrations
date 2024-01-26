<?php

declare(strict_types=1);

namespace StrackIntegrations\Util;

interface CustomFieldsInterface
{
    // Customer custom fields
    public const CUSTOMER_CUSTOM_FIELD_SET = 'strack_integrations_customer';
    public const CUSTOMER_MINIMUM_ORDER_VALUE = 'strack_customer_minimum_order_value';

    // Order custom fields
    public const ORDER_CUSTOM_FIELD_SET = 'strack_integrations_order';
    public const ORDER_IS_OFFER = 'strack_order_has_price_error';
    public const ORDER_REQUESTED_DELIVERY_DATE = 'strack_order_requested_delivery_date';
    public const ORDER_IS_PARTIAL_DELIVERY = 'strack_order_is_partial_delivery';
    public const ORDER_OWN_ORDER_NUMBER = 'strack_order_own_order_number';
    public const ORDER_OFFER_NUMBER = 'strack_order_offer_number';
    public const ORDER_COMMENT = 'strack_order_comment';

    // Cart/Order position payload keys
    public const ORDER_POSITION_OWN_PART_NUMBER = 'strack_order_position_own_part_number';
    public const ORDER_POSITION_COMMENT = 'strack_order_position_comment';
}
