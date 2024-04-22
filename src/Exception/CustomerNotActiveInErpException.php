<?php

declare(strict_types=1);

namespace StrackIntegrations\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('customer-order')]
class CustomerNotActiveInErpException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('Customer is not activated in ERP.');
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__CUSTOMER_NOT_ACTIVATED_IN_ERP';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
