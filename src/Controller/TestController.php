<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use GuzzleHttp\Exception\BadResponseException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use StrackIntegrations\Client\OrderClient;
use StrackIntegrations\Client\PriceClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends StorefrontController
{
    public function __construct(
        protected PriceClient $priceClient,
        protected OrderClient $orderClient,
    ) {
    }

    #[Route('/StrackIntegrationsTestController/testOrder', name: 'frontend.StrackIntegrations.testOrder', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function testOrder(SalesChannelContext $context): Response
    {
        $orders = $this->orderClient->getOrderList('10017', '1');
        $ordersWithFilter = $this->orderClient->getOrderList('10017', '1', '2023-08-31');
        $orderDetails = $this->orderClient->getOrderItems('3352228', '1');
        return $this->json(['success' => true]);
    }

    #[Route('/StrackIntegrationsTestController/testRoute', name: 'frontend.StrackIntegrations.testRoute', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function testRoute(SalesChannelContext $context): Response
    {
        return $this->json(['success' => true]);
    }

    #[Route('/StrackIntegrationsTestController/testRoute', name: 'frontend.StrackIntegrations.testRoute', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function orderTest(SalesChannelContext $context): Response
    {
        return $this->json(['success' => true]);
    }

    #[Route('/StrackIntegrationsTestController/singlePriceTest', name: 'frontend.StrackIntegrations.singlePriceTest', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function singlePriceTest(SalesChannelContext $context): Response
    {
        try {
            $test = $this->priceClient->getSalesPrice('10001868', '173297', $context->getCurrency()->getIsoCode(), 4);
            var_dump(json_encode([
                'productNumber' => $test->getProductNumber(),
                'unitPrice' => $test->getUnitPrice(),
                'quantity' => $test->getQuantity(),
                'percentageLineDiscount' => $test->getPercentageLineDiscount(),
                'totalPrice' => $test->getTotalPrice(),
                'totalPriceWithVar' => $test->getTotalPriceWithVat(),
                'debtorNumber' => $test->getDebtorNumber(),
                'currencyIso' => $test->getCurrencyIso(),
                'isBrutto' => $test->isBrutto()
            ]));
            exit;
        } catch(BadResponseException $exception) {
            var_dump($exception->getResponse()->getBody()->getContents());
            exit;
        }
    }

    #[Route('/StrackIntegrationsTestController/batchPriceTest', name: 'frontend.StrackIntegrations.batchPriceTest', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function batchPriceTest(SalesChannelContext $context): Response
    {
        try {
            $test = $this->priceClient->getSalesPrices('10001868', ['173297' => 1, '85119' => 1, '85122' => 1], $context->getCurrency()->getIsoCode());
//            $notWorking = $this->priceClient->getSalesPrices('10001868', ['173297' => 1, '85119' => 1, '85122' => 1, '932853842538' => 2], $context->getCurrency()->getIsoCode());
            $outputArray = [];

            foreach($test as $row) {
                $outputArray[] = [
                    'productNumber' => $row->getProductNumber(),
                    'unitPrice' => $row->getUnitPrice(),
                    'quantity' => $row->getQuantity(),
                    'percentageLineDiscount' => $row->getPercentageLineDiscount(),
                    'totalPrice' => $row->getTotalPrice(),
                    'totalPriceWithVar' => $row->getTotalPriceWithVat(),
                    'debtorNumber' => $row->getDebtorNumber(),
                    'currencyIso' => $row->getCurrencyIso(),
                    'isBrutto' => $row->isBrutto()
                ];
            }

            var_dump(json_encode($outputArray));
            exit;
        } catch(BadResponseException $exception) {
            var_dump($exception->getResponse()->getBody()->getContents());
            exit;
        }
    }
}
