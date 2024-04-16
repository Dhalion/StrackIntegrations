<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use StrackOci\Models\OciSession;
use StrackVariantsTable\Pagelet\VariantsTableRowsPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class VariantsTableSubscriber implements EventSubscriberInterface
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
            VariantsTableRowsPageletLoadedEvent::class => 'addCustomerPrices'
        ];
    }

    public function addCustomerPrices(VariantsTableRowsPageletLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $debtorNumber = $this->apiConfig->isTestModeOn() ? $this->apiConfig->getTestModeDebtorNumber() : $customer->getId();

        if(!$this->apiConfig->isTestModeOn() && ($ociSession = $event->getRequest()->getSession()->get(OciSession::OCI_SESSION_NAME)) && $ociSession instanceof OciSession && $ociSession->getAdditionalFieldByKey('customer')) {
            $debtorNumber = $ociSession->getAdditionalFieldByKey('customer')->getId();
        }

        $variants = $event->getPagelet()->getVariants();

        $priceRequestBatch = [];

        foreach($variants as $variant) {
            $priceRequestBatch[$variant->getProductNumber()] = $variant->getMinPurchase() ?: 1;
        }

        try {
            $customerPrices = $this->priceClient->getSalesPrices($debtorNumber, $priceRequestBatch, $event->getSalesChannelContext()->getCurrency()->getIsoCode());
            $this->priceTransformer->setCalculatedPrices($customerPrices, $variants);
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return;
        }
    }
}
