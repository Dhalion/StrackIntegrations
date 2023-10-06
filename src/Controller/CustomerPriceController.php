<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Product\ProductPageLoader;
use StrackIntegrations\Client\PriceClient;
use StrackIntegrations\Config\ApiConfig;
use StrackIntegrations\Exception\MissingDebtorNumberException;
use StrackIntegrations\Exception\MissingParameterException;
use StrackIntegrations\Logger\Logger;
use StrackIntegrations\Service\PriceTransformer;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomerPriceController extends StorefrontController
{
    public function __construct(
        protected PriceClient $priceClient,
        protected PriceTransformer $priceTransformer,
        protected Logger $logger,
        protected ProductPageLoader $productPageLoader,
        protected ApiConfig $apiConfig
    ) {
    }

    #[Route('/customer-price', name: 'frontend.StrackIntegrations.customerPrice', defaults: ['_routeScope' => ['storefront'], 'csrf_protected' => false, '_loginRequired' => true, 'XmlHttpRequest' => true], methods: ['POST'])]
    public function getCustomerPrice(SalesChannelContext $context, Request $request): Response
    {
        $productId = $request->get('productId');
        $quantity = (int)$request->get('quantity');
        $isComponent = (bool)$request->get('isComponent');

        if(!$productId) {
            throw new MissingParameterException('productId');
        }

        if(!$quantity) {
            throw new MissingParameterException('quantity');
        }


        $debtorNumber = $this->apiConfig->isTestModeOn() ? $this->apiConfig->getTestModeDebtorNumber() : $context->getCustomer()->getId();

        if(!$debtorNumber) {
            throw new MissingDebtorNumberException($context->getCustomer()->getCustomerNumber());
        }

        $request->attributes->set('productId', $productId);
        $page = $this->productPageLoader->load($request, $context);

        $product = $page->getProduct();

        $customerPrice = $this->priceClient->getSalesPrice($debtorNumber, $product->getProductNumber(), $context->getCurrency()->getIsoCode(), $quantity);
        $this->priceTransformer->setCalculatedPrice($customerPrice, $product);

        $template = $isComponent ?
            '@Storefront/storefront/component/buy-widget/buy-widget-price.html.twig' :
            '@Storefront/storefront/page/product-detail/buy-widget-price.html.twig';

        return $this->renderStorefront($template, [
           'page' => $page
        ]);
    }
}
