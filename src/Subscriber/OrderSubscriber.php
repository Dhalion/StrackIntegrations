<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use StrackIntegrations\Struct\LiveCalculatedPrice;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;

readonly class OrderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => ['onCartConvertedEvent', -9999],
        ];
    }

    public function onCartConvertedEvent(CartConvertedEvent $event): void
    {
        $convertedCart = $event->getConvertedCart();
        foreach($convertedCart['lineItems'] as &$lineItem) {
            if($lineItem['price'] instanceof LiveCalculatedPrice) {
                $lineItem['payload']['hasLivePriceError'] = $lineItem['price']->hasError();
            }
        }

        $event->setConvertedCart($convertedCart);
    }
}
