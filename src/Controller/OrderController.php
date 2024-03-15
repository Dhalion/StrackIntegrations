<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use StrackIntegrations\Page\Order\OrderPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class OrderController extends StorefrontController
{
    public function __construct(
        private readonly OrderPageLoader $pageLoader
    ) {
    }

    // This route replaces b2b_order.controller, so it remains accessible as /b2border
    #[Route('/orders', name: 'frontend.b2b.orders.index', options: ['seo' => false], defaults: ['_noStore' => true, '_loginRequired' => true], methods: ['GET'])]
    public function indexAction(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->pageLoader->load($request, $context);
        return $this->renderStorefront('@Storefront/storefront/page/orders/index.html.twig', ['page' => $page]);
    }

    #[Route('/order-items', name: 'frontend.b2b.orders.order-items', options: ['seo' => false], defaults: ['_noStore' => true, '_loginRequired' => true, 'XmlHttpRequest' => true], methods: ['GET'])]
    public function itemsAction(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->pageLoader->loadItems($request, $context);
        return $this->renderStorefront('@Storefront/storefront/page/orders/order-items.html.twig', ['page' => $page]);
    }
}
