<?php declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductEntityLoadedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.' . ProductEvents::PRODUCT_LOADED_EVENT => ['onProductLoaded', 100]
        ];
    }

    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        // only affects sales channel source
        if (!($event->getContext()->getSource() instanceof SalesChannelApiSource))
            return;

        /*************************************************************************************
         * Set initially loaded prices to 0
         *
         * This is done, to prevent leaking the db price in any processes, meta properties, comparisons, tax calculations and pages
         * until the price was properly loaded and transformed from the ERP.
         *************************************************************************************/
        foreach ($event->getEntities() as $product) {
            $this->unsetProductPrice($product);
        }
    }

    private function unsetProductPrice(SalesChannelProductEntity $product): void
    {
        $basePrices = $product->getPrice();
        $extendedPrices = $product->getPrices();
        $purchasePrices = $product->getPurchasePrices();
        $cheapestPrice = $product->getCheapestPrice();

        if ($basePrices) {
            foreach ($basePrices as $basePrice) {
                $basePrice->setNet(0.0);
                $basePrice->setGross(0.0);
            }
        }

        if ($extendedPrices) {
            foreach ($extendedPrices as $extendedPrice) {
                $graduatedPrices = $extendedPrice->getPrice();
                foreach ($graduatedPrices as $graduatedPrice) {
                    $graduatedPrice->setNet(0.0);
                    $graduatedPrice->setGross(0.0);
                }
            }
        }

        if ($purchasePrices) {
            foreach ($purchasePrices as $purchasePrice) {
                $purchasePrice->setNet(0.0);
                $purchasePrice->setGross(0.0);
            }
        }

        if ($cheapestPrice instanceof CheapestPriceContainer) {
            $newContainer = $cheapestPrice;
            $newContainer->assign(['default' => []]);
            $newContainer->assign(['value' => []]);
        }

        if ($cheapestPrice instanceof CheapestPrice) {
            $cheapestPrices = $cheapestPrice->getPrice();
            foreach ($cheapestPrices as $cheapestPrice) {
                $cheapestPrice->setGross(0.0);
                $cheapestPrice->setNet(0.0);
            }
        }
    }
}
