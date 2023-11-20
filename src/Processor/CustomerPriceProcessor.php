<?php

declare(strict_types=1);

namespace StrackIntegrations\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use StrackIntegrations\Struct\SalesPrice;

readonly class CustomerPriceProcessor implements CartDataCollectorInterface, CartProcessorInterface
{
    public function __construct(
        private PriceClient $priceClient,
        private PriceTransformer $priceTransformer,
        private Logger $logger,
        private ApiConfig $apiConfig
    ) {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $customer = $context->getCustomer();
        $lineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if (!$customer) {
            return;
        }

        $debtorNumber = $this->apiConfig->isTestModeOn() ? $this->apiConfig->getTestModeDebtorNumber() : $customer->getId();

        $priceBatchPayload = [];

        foreach ($lineItems as $lineItem) {
            $productNumber = $lineItem->getPayloadValue('productNumber');
            $price = $lineItem->getPrice();

            if (!$productNumber || !$price || $lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $priceBatchPayload[$productNumber] = $lineItem->getQuantity();
        }

        try {
            $customerPrices = $this->priceClient->getSalesPrices($debtorNumber, $priceBatchPayload, $context->getCurrency()->getIsoCode());
        } catch(\Exception $exception) {
            $this->logger->logException(self::class, $exception);
            return;
        }

        foreach ($lineItems as $lineItem) {
            $productNumber = $lineItem->getPayloadValue('productNumber');
            $price = $lineItem->getPrice();

            if (!$productNumber || !$price || $lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            try {
                $key = $this->buildKey($lineItem->getId());
                $customerPrice = $customerPrices->filter(function(SalesPrice $salesPrice) use($productNumber) {return $salesPrice->getProductNumber() === $productNumber;})->first();
                if(!$customerPrice) {
                    continue;
                }

                $calculatedPrice = $this->priceTransformer->getCalculatedPrice($customerPrice, $lineItem->getQuantity(), $price->getTaxRules());

                $data->set($key, $calculatedPrice);
            } catch (\Exception) {
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

            /** @var CalculatedPrice $newPrice */
            $newPrice = $data->get($key);

            $definition = new QuantityPriceDefinition(
                $newPrice->getUnitPrice(),
                $newPrice->getTaxRules(),
                $newPrice->getQuantity(),
            );

            $product->setPrice($newPrice);
            $product->setPriceDefinition($definition);
        }
    }

    private function buildKey(string $id): string
    {
        return 'customer-price-'.$id;
    }
}
