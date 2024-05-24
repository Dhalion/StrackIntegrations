<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\CustomerErpService;
use StrackIntegrations\Service\PriceTransformer;
use StrackIntegrations\Struct\SalesPrice;
use StrackOci\Models\OciSession;
use StrackSwitchWizard\Page\AccessoriesPage\AccessoriesPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class AccessoriesPageSubscriber implements EventSubscriberInterface
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
            AccessoriesPageLoadedEvent::class => 'addCustomerPrices'
        ];
    }

    public function addCustomerPrices(AccessoriesPageLoadedEvent $event): void
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

        $priceRequestBatch = [];

        $page = $event->getPage();
        $machineProducts = $page->getMachineSelectedProducts()->getAllSalesChannelProducts();

        if($machineProducts->count() > 0) {
            foreach($machineProducts as $product) {
                if(PriceClient::shouldPreventLivePrice($product->getCustomFieldsValue(PriceClient::SHOULD_DO_LIVE_PRICE_CUSTOM_FIELD))) {
                    continue;
                }
                $priceRequestBatch[$product->getProductNumber()] = $product->getMinPurchase() ?: 1;
            }
        }

        $lightProducts = $event->getPage()->getLightSelectedProducts()->getAllSalesChannelProducts();
        if($lightProducts->count() > 0) {
            foreach($lightProducts as $product) {
                if(PriceClient::shouldPreventLivePrice($product->getCustomFieldsValue(PriceClient::SHOULD_DO_LIVE_PRICE_CUSTOM_FIELD))) {
                    continue;
                }
                $priceRequestBatch[$product->getProductNumber()] = $product->getMinPurchase() ?: 1;
            }
        }

        if(count($priceRequestBatch) === 0) {
            return;
        }

        try {
            $customerPrices = $this->priceClient->getSalesPrices($debtorNumber, $priceRequestBatch, $event->getSalesChannelContext()->getCurrency()->getIsoCode(), $ignoreCall);
            if($machineProducts->count() > 0) {
                foreach($page->getMachineSelectedProducts() as $selectedProduct) {
                    if(!$selectedProduct->getProduct() instanceof SalesChannelProductEntity) {
                        continue;
                    }

                    $customerPrice = $customerPrices->filter(function(SalesPrice $salesPrice) use($selectedProduct) {return $salesPrice->getProductNumber() === $selectedProduct->getProduct()->getProductNumber();})->first();
                    if(!$customerPrice) {
                        continue;
                    }

                    $this->priceTransformer->setCalculatedPrice($customerPrice, $selectedProduct->getProduct());
                }
            }
            if($lightProducts->count() > 0) {
                foreach($page->getLightSelectedProducts() as $selectedProduct) {
                    if(!$selectedProduct->getProduct() instanceof SalesChannelProductEntity) {
                        continue;
                    }

                    $customerPrice = $customerPrices->filter(function(SalesPrice $salesPrice) use($selectedProduct) {return $salesPrice->getProductNumber() === $selectedProduct->getProduct()->getProductNumber();})->first();
                    if(!$customerPrice) {
                        continue;
                    }

                    $this->priceTransformer->setCalculatedPrice($customerPrice, $selectedProduct->getProduct());
                }
            }
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return;
        }
    }
}
