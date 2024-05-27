<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CartBeforeSerializationEvent;
use StrackIntegrations\Client\PriceClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CartSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CartBeforeSerializationEvent::class => 'onBeforeSerialization',
        ];
    }

    public function onBeforeSerialization(CartBeforeSerializationEvent $event): void
    {
        $allowed = $event->getCustomFieldAllowList();
        $allowed[] = 'strack_montage';
        $allowed[] = PriceClient::SHOULD_DO_LIVE_PRICE_CUSTOM_FIELD;

        $event->setCustomFieldAllowList($allowed);
    }
}
