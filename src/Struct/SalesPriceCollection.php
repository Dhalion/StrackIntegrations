<?php

declare(strict_types=1);

namespace StrackIntegrations\Struct;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method void               add(SalesPrice $struct)
 * @method void               set(string $key, SalesPrice $struct)
 * @method SalesPrice[]    getIterator()
 * @method SalesPrice[]    getElements()
 * @method null|SalesPrice get(string $key)
 * @method null|SalesPrice first()
 * @method null|SalesPrice last()
 */
class SalesPriceCollection extends Collection
{
    public function getExpectedClass(): string
    {
        return SalesPrice::class;
    }
}
