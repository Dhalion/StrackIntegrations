<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ProductPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PriceClient $priceClient,
        private PriceTransformer $priceTransformer,
        private Logger $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'addCustomerPrices'
        ];
    }

    public function addCustomerPrices(ProductPageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer || !isset($customer->getCustomFields()[CustomFieldsInterface::CUSTOMER_DEBTOR_NUMBER])) {
            return;
        }

        $debtorNumber = $customer->getCustomFields()[CustomFieldsInterface::CUSTOMER_DEBTOR_NUMBER];
        $product = $event->getPage()->getProduct();

        $startingQuantity = $product->getMinPurchase() ?: 1;

        try {
            $customerPrice = $this->priceClient->getSalesPrice($debtorNumber, $product->getProductNumber(), $startingQuantity);
            $product->setCalculatedPrice($this->priceTransformer->getCalculatedPrice($customerPrice, $product->getMinPurchase() ?: 1, $product->getCalculatedPrice()->getTaxRules()));
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            $event->getPage()->assign(['customerPriceError' => true]);
            return;
        }

        $event->getPage()->assign(['customerPriceSuccess' => true]);
    }
}
