<?php

declare(strict_types=1);

namespace StrackIntegrations\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use StrackIntegrations\Page\Offer\OfferPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class OfferController extends StorefrontController
{
    public function __construct(
        private readonly OfferPageLoader $pageLoader
    ) {
    }

    // This route replaces b2b_order.controller, so it remains accessible as /b2boffer
    #[Route('/offers', name: 'frontend.b2b.offers.index', options: ['seo' => false], defaults: ['_noStore' => true, '_loginRequired' => true], methods: ['GET'])]
    public function indexAction(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->pageLoader->load($request, $context);
        return $this->renderStorefront('@Storefront/storefront/page/offers/index.html.twig', ['page' => $page]);
    }

    #[Route('/offer-items', name: 'frontend.b2b.offers.offer-items', options: ['seo' => false], defaults: ['_noStore' => true, '_loginRequired' => true, 'XmlHttpRequest' => true], methods: ['GET'])]
    public function itemsAction(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->pageLoader->loadItems($request, $context);
        return $this->renderStorefront('@Storefront/storefront/page/offers/offer-items.html.twig', ['page' => $page]);
    }
}
