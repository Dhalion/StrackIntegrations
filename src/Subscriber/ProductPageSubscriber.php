<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ProductPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PriceClient $priceClient,
        private PriceTransformer $priceTransformer,
        private Logger $logger,
        private ApiConfig $apiConfig
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

        if (!$customer) {
            return;
        }

        $debtorNumber = $this->apiConfig->isTestModeOn() ? $this->apiConfig->getTestModeDebtorNumber() : $customer->getId();
        $product = $event->getPage()->getProduct();

        $startingQuantity = $product->getMinPurchase() ?: 1;

        try {
            $customerPrice = $this->priceClient->getSalesPrice($debtorNumber, $product->getProductNumber(), $event->getSalesChannelContext()->getCurrency()->getIsoCode(), $startingQuantity);
            $this->priceTransformer->setCalculatedPrice($customerPrice, $product);
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
        }
    }
}
