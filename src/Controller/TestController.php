<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use GuzzleHttp\Exception\BadResponseException;
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
    public function generateProductDataFeed(): Response
    {
        try {
            $test = $this->priceClient->getSalesPrice('10001868', '173297', 4);
            var_dump($test);
            exit;
        } catch(BadResponseException $exception) {
            var_dump($exception->getResponse()->getBody()->getContents());
            exit;
        }
    }
}
