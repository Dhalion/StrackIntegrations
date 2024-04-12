<?php

declare(strict_types=1);

namespace StrackIntegrations\Service;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;

readonly class CurrencyService
{
    public function __construct(
        private EntityRepository $currencyRepository,
        private EntityRepository $salesChannelRepository,
        private CacheInterface $cache,
        private SalesChannelContextPersister $contextPersister,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function getCurrencyId(string $isoCode, Context $context): ?string
    {
        return $this->cache->get('currency_id_' . $isoCode, function (ItemInterface $item) use ($isoCode, $context) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));
            $currencyId = $this->currencyRepository->searchIds($criteria, $context)->firstId();

            if (!$currencyId) {
                $item->expiresAfter(0);
                return null;
            }

            $item->expiresAfter(604800);
            return $currencyId;
        });
    }

    public function doesSalesChannelHasCurrency(string $currencyId,  string $salesChannelId, Context $context): bool
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('currencies.id', $currencyId));
        return $this->salesChannelRepository->searchIds($criteria, $context)->firstId() !== null;
    }

    public function persistCurrencyForCustomer(string $currencyId, CustomerEntity $customer, SalesChannelContext $context): void
    {
        if ($currencyId === $context->getCurrencyId()) {
            return;
        }

        $this->contextPersister->save(
            $context->getToken(),
            ['currencyId' => $currencyId],
            $context->getSalesChannel()->getId(),
            $customer->getId()
        );

        $event = new SalesChannelContextSwitchEvent($context, new RequestDataBag(['currencyId' => $currencyId]));
        $this->eventDispatcher->dispatch($event);
    }
}
