<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\CustomerErpService;
use StrackIntegrations\Service\PriceTransformer;
use StrackOci\Models\OciSession;
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
        $ignoreCall = CustomerErpService::isCustomerActive($customer) === false;

        if(!$this->apiConfig->isTestModeOn() && ($ociSession = $event->getRequest()->getSession()->get(OciSession::OCI_SESSION_NAME)) && $ociSession instanceof OciSession && $ociSession->getAdditionalFieldByKey('customer')) {
            $debtorNumber = $ociSession->getAdditionalFieldByKey('customer')->getId();
            $ignoreCall = CustomerErpService::isCustomerActive($ociSession->getAdditionalFieldByKey('customer')) === false;
        }

        $product = $event->getPage()->getProduct();

        $startingQuantity = $product->getMinPurchase() ?: 1;

        try {
            $customerPrice = $this->priceClient->getSalesPrice($debtorNumber, $product->getProductNumber(), $event->getSalesChannelContext()->getCurrency()->getIsoCode(), $startingQuantity, $ignoreCall);
            $this->priceTransformer->setCalculatedPrice($customerPrice, $product);
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
        }
    }
}
