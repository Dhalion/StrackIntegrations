<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use StrackIntegrations\Exception\CustomerNotActiveInErpException;
use StrackIntegrations\Service\CustomerErpService;
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
        if (!CustomerErpService::isCustomerActive($event->getSalesChannelContext()->getCustomer())) {
            throw new CustomerNotActiveInErpException();
        }

        $request = $this->requestStack->getCurrentRequest();

        if(!$request) {
            $this->requestStack->getMainRequest();
        }

        if(!$request) {
            return;
        }

        $ownPartNumbers = $request->get(CustomFieldsInterface::ORDER_POSITION_OWN_PART_NUMBER, []);
        $positionComments = $request->get(CustomFieldsInterface::ORDER_POSITION_COMMENT, []);

        $convertedCart = $event->getConvertedCart();
        foreach($convertedCart['lineItems'] as &$lineItem) {
            if($lineItem['price'] instanceof LiveCalculatedPrice) {
                $lineItem['payload']['hasLivePriceError'] = $lineItem['price']->hasError();
            }

            $ownPartNumber = $ownPartNumbers[$lineItem['identifier']] ?? null;
            if($ownPartNumber) {
                $ownPartNumber = substr($ownPartNumber, 0, CustomFieldsInterface::ORDER_POSITION_OWN_PART_NUMBER_MAX_LENGTH);
            }

            $lineItem['payload'][CustomFieldsInterface::ORDER_POSITION_OWN_PART_NUMBER] = $ownPartNumber;

            $positionComment = $positionComments[$lineItem['identifier']] ?? null;
            if($positionComment) {
                $positionComment = substr($positionComment, 0, CustomFieldsInterface::ORDER_POSITION_COMMENT_MAX_LENGTH);
            }

            $lineItem['payload'][CustomFieldsInterface::ORDER_POSITION_COMMENT] = $positionComment;
            $identifier = $lineItem['identifier'];

            $originalLineItem = $event->getCart()->getLineItems()->filter(function(LineItem $lineItem) use ($identifier) { return $identifier === $lineItem->getId();} )->first();
            if ($originalLineItem && $originalLineItem->getDeliveryInformation()) {
                $lineItem['payload']['strack_stock'] = $originalLineItem->getDeliveryInformation()->getStock();
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
        $orderRequestedDeliveryDate = $request->get(CustomFieldsInterface::ORDER_REQUESTED_DELIVERY_DATE);
        $orderIsPartialDelivery = $request->get(CustomFieldsInterface::ORDER_IS_PARTIAL_DELIVERY) === 'on';
        $orderOwnOrderNumber = substr($request->get(CustomFieldsInterface::ORDER_OWN_ORDER_NUMBER, ''), 0, CustomFieldsInterface::ORDER_OWN_ORDER_NUMBER_MAX_LENGTH);
        $orderOfferNumber = substr($request->get(CustomFieldsInterface::ORDER_OFFER_NUMBER, ''), 0, CustomFieldsInterface::ORDER_OFFER_NUMBER_MAX_LENGTH);
        $orderComment = $request->get(CustomFieldsInterface::ORDER_COMMENT);

        if($orderComment) {
            $orderComment = substr($orderComment, 0, CustomFieldsInterface::ORDER_COMMENT_MAX_LENGTH);
        }

        $order = $event->getOrder();
        $isOffer = $orderIsOffer || $this->hasPriceError($order->getLineItems());

        $customFields = [
            CustomFieldsInterface::ORDER_IS_OFFER => $isOffer,
            CustomFieldsInterface::ORDER_REQUESTED_DELIVERY_DATE => $orderRequestedDeliveryDate,
            CustomFieldsInterface::ORDER_IS_PARTIAL_DELIVERY => $orderIsPartialDelivery,
            CustomFieldsInterface::ORDER_OWN_ORDER_NUMBER => $orderOwnOrderNumber,
            CustomFieldsInterface::ORDER_OFFER_NUMBER => $orderOfferNumber,
        ];

        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'customFields' => $customFields,
                'customerComment' => $orderComment
            ]
        ], $event->getContext());

        $actualCustomFields = $order->getCustomFields() ?? [];
        $actualCustomFields = array_merge($actualCustomFields, $customFields);
        $order->setCustomFields($actualCustomFields);
        $order->setCustomerComment($orderComment);
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
