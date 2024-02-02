<?php declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

readonly class CartLoadedSubscriber implements EventSubscriberInterface {
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'handleMinimumOrderValue',
            OffcanvasCartPageLoadedEvent::class => 'handleMinimumOrderValue',
            CheckoutConfirmPageLoadedEvent::class => 'handleMinimumOrderValue'
        ];
    }

    public function handleMinimumOrderValue(
        CheckoutCartPageLoadedEvent |
        OffcanvasCartPageLoadedEvent |
        CheckoutConfirmPageLoadedEvent $event): void {

            $customer = $event->getSalesChannelContext()->getCustomer();
            if (!$customer) {
                return;
            }

            $minimumOrderValue = $customer->getCustomFields()[
                CustomFieldsInterface::CUSTOMER_MINIMUM_ORDER_VALUE
            ] ?? null;
            if (!$minimumOrderValue) {
                return;
            }

            $cart = $event->getPage()->getCart();
            $cartAmount = $cart->getPrice()->getTotalPrice();

            if ($cartAmount >= $minimumOrderValue) {
                return;
            }

            $message = $this->translator->trans('StrackIntegrations.cart-checkout.cartBelowMinimumOrderValue', [
                '%minOrderVal%' => $minimumOrderValue,
            ]);

            // Show flashbag only on these routes
            $routesToShowFlashBag = [
                "/checkout/offcanvas",
                "/checkout/cart",
                "/checkout/confirm"
            ];

            $loadedRoute = $this->requestStack->getCurrentRequest()->get("resolved-uri");

            if (!in_array($loadedRoute, $routesToShowFlashBag)) {
                return;
            }

            $this->requestStack->getCurrentRequest()->getSession()->getFlashBag()->add('danger', $message);
    }

}