<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\CurrencyService;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CustomerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CurrencyService $currencyService,
        private Logger $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLoginEvent::class => 'onCustomerLogin'
        ];
    }

    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $customer = $event->getCustomer();
        $currencyIsoCode = $customer->getCustomFieldsValue(CustomFieldsInterface::CUSTOMER_CURRENCY_CODE);
        if (!$currencyIsoCode || !$event->getSalesChannelId()) {
            return;
        }

        try {
            $currencyId = $this->currencyService->getCurrencyId($currencyIsoCode, $event->getContext());

            if (!$currencyId) {
                return;
            }

            if (!$this->currencyService->doesSalesChannelHasCurrency($currencyId, $event->getSalesChannelId(), $event->getContext())) {
                return;
            }

            $this->currencyService->persistCurrencyForCustomer($currencyId, $customer, $event->getSalesChannelContext());
        } catch(\Throwable $exception) {
            $this->logger->logException(self::class, $exception);
        }
    }
}
