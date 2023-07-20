<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Struct\CustomerListPrice;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

//todo try to pack the calculation logic to the another service, for reuse.
readonly class ProductPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PriceClient $priceClient,
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
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            $event->getPage()->assign(['customerPriceError' => true]);
            return;
        }

        $listPrice = null;
        if($customerPrice->getPercentageLineDiscount()) {
            $listPrice = new CustomerListPrice(
                $customerPrice->getUnitPrice() * $startingQuantity,
                $customerPrice->getUnitPrice() * $startingQuantity - $customerPrice->getTotalPrice(),
                $customerPrice->getPercentageLineDiscount()
            );
        }

        $product->setCalculatedPrice(new CalculatedPrice(
            $customerPrice->getTotalPrice(),
            $customerPrice->getTotalPrice(),
            $product->getCalculatedPrice()->getCalculatedTaxes(), //todo
            $product->getCalculatedPrice()->getTaxRules(),
            (int)$customerPrice->getQuantity(),
            null,
            $listPrice
        ));

        $event->getPage()->assign(['customerPriceSuccess' => true]);
    }
}
