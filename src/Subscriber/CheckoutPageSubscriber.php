<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use StrackIntegrations\Struct\LiveCalculatedPrice;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPage'
        ];
    }

    public function onCheckoutConfirmPage(CheckoutConfirmPageLoadedEvent $event): void
    {
        $hasLivePriceError = $event->getPage()->getCart()->getLineItems()->filter(function(LineItem $lineItem) {
            return $lineItem->getPrice() instanceof LiveCalculatedPrice && $lineItem->getPrice()->hasError();
        })->count() > 0;

        $event->getPage()->assign([
            'hasLivePriceError' => $hasLivePriceError
        ]);
    }
}
