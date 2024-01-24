<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use StrackIntegrations\Struct\LiveCalculatedPrice;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityRepository $orderRepository,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => ['onCartConvertedEvent', -9999],
            CheckoutOrderPlacedEvent::class => 'onOrderPlacedEvent'
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

    public function onOrderPlacedEvent(CheckoutOrderPlacedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if(!$request) {
            $request = $this->requestStack->getMainRequest();
        }

        if(!$request) {
            return;
        }

        $orderIsOffer = (bool)$request->get('orderIsOffer');
        $order = $event->getOrder();
        $isOffer = $orderIsOffer || $this->hasPriceError($order->getLineItems());

        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'customFields' => [
                    CustomFieldsInterface::ORDER_IS_OFFER => $isOffer
                ]
            ]
        ], $event->getContext());

        $customFields = $order->getCustomFields() ?? [];
        $customFields[CustomFieldsInterface::ORDER_IS_OFFER] = $isOffer;
        $order->setCustomFields($customFields);
    }

    private function hasPriceError(?OrderLineItemCollection $lineItems): bool
    {
        if(!$lineItems) {
            return false;
        }

        /** @var OrderLineItemEntity $lineItem */
        foreach($lineItems as $lineItem) {
            if(isset($lineItem->getPayload()['hasLivePriceError']) && $lineItem->getPayload()['hasLivePriceError']) {
                return true;
            }
        }

        return false;
    }
}
