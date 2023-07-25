<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use GuzzleHttp\Exception\BadResponseException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use StrackIntegrations\Client\PriceClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends StorefrontController
{
    public function __construct(
        protected PriceClient $priceClient
    ) {
    }

    #[Route('/StrackIntegrations/test', name: 'frontend.StrackIntegrations.test', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false], methods: ['GET'])]
    public function generateProductDataFeed(SalesChannelContext $context): Response
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
}
