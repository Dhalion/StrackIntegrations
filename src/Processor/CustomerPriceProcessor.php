<?php

declare(strict_types=1);

namespace StrackIntegrations\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use StrackIntegrations\Util\CustomFieldsInterface;

readonly class CustomerPriceProcessor implements CartDataCollectorInterface, CartProcessorInterface
{
    public function __construct(
        private PriceClient $priceClient,
        private PriceTransformer $priceTransformer,
        private Logger $logger
    ) {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $customer = $context->getCustomer();
        $lineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if (!$customer || !isset($customer->getCustomFields()[CustomFieldsInterface::CUSTOMER_DEBTOR_NUMBER])) {
            return;
        }

        $debtorNumber = $customer->getCustomFields()[CustomFieldsInterface::CUSTOMER_DEBTOR_NUMBER];

        foreach ($lineItems as $lineItem) {
            $productNumber = $lineItem->getPayloadValue('productNumber');
            $price = $lineItem->getPrice();

            if (!$productNumber || !$price) {
                continue;
            }

            try {
                $key = $this->buildKey($lineItem->getId());
                $customerPrice = $this->priceClient->getSalesPrice($debtorNumber, $productNumber, $lineItem->getQuantity());
                $calculatedPrice = $this->priceTransformer->getCalculatedPrice($customerPrice, $lineItem->getQuantity(), $price->getTaxRules());

                // we have to set a value for each line item to prevent duplicate queries in next calculation
                $data->set($key, $calculatedPrice);
            } catch (\Exception $exception) {
                $this->logger->logException(self::class, $exception);
                $lineItem->setPayloadValue('customerPriceError', true);
                continue;
            }
        }
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $products = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $key = $this->buildKey($product->getId());

            if (!$data->has($key) || $data->get($key) === null) {
                continue;
            }

            $newPrice = $data->get($key);

            $definition = new QuantityPriceDefinition(
                $newPrice->getUnitPrice(),
                $newPrice->getTaxRules(),
                $newPrice->getQuantity(),
            );

            $product->setPrice($newPrice);
            $product->setPriceDefinition($definition);
            $product->setPayloadValue('customerPriceSuccess', true);
        }
    }

    private function buildKey(string $id): string
    {
        return 'customer-price-'.$id;
    }
}
