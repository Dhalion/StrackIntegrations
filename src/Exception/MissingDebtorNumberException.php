<?php

declare(strict_types=1);

namespace StrackIntegrations\Exception;

class MissingDebtorNumberException extends \Exception
{
    public function __construct(string $customerNumber)
    {
        parent::__construct(sprintf('Missing debtor number with customer: %s', $customerNumber));
    }
}
