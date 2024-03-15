<?php

declare(strict_types=1);

namespace StrackIntegrations\Page\Order;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Defaults;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Page;
use StrackIntegrations\Client\OrderClient;
use StrackIntegrations\Config\ApiConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class OrderPageLoader
{
    public function __construct(
        private GenericPageLoaderInterface $genericPageLoader,
        private OrderClient $client,
        private ApiConfig $apiConfig,
    ) {
    }

    public function load(Request $request, SalesChannelContext $context): OrderPage
    {
        $page = $this->getBasicPage($request, $context);

        /** @var OrderPage $page */
        $page = OrderPage::createFrom($page);
        $customerNumber = $this->getCustomerNumber($context->getCustomer()->getCustomerNumber());
        $documentType = $request->query->get('documentType', '1');
        $documentType = $this->parseDocumentTypeEnum($documentType);

        $page->setDocumentType($documentType);

        if ($request->query->has('orderFrom')) {
            try {
                $fromDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$request->query->get('orderFrom'));
                if ($fromDate) {
                    $page->setOrderFrom($fromDate);
                }
            } catch (\Exception $e) {
            }
        }

        if ($request->query->has('orderTo')) {
            try {
                $toDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$request->query->get('orderTo'));
                if ($toDate) {
                    $page->setOrderTo($toDate);
                }
            } catch (\Exception $e) {
            }
        }


        $result = $this->client->getOrderList($customerNumber, $page->getDocumentType(), $page->getOrderFrom()->format(Defaults::STORAGE_DATE_FORMAT), $page->getOrderTo()->format(Defaults::STORAGE_DATE_FORMAT));

        $page->setIsSuccess($result['success']);
        $page->setOrders($result['data']);

        return $page;
    }

    public function loadItems(Request $request, SalesChannelContext $context): OrderItemsPage
    {
        if(!$request->query->has('orderNumber')) {
            throw new BadRequestHttpException('Parameter orderNumber is required!');
        }

        $documentType = $request->query->get('documentType', '1');
        $documentType = $this->parseDocumentTypeEnum($documentType);

        $page = $this->getBasicPage($request, $context);

        /** @var OrderItemsPage $page */
        $page = OrderItemsPage::createFrom($page);
        $page->setDocumentType($documentType);

        $result = $this->client->getOrderItems($request->query->get('orderNumber'), $page->getDocumentType());

        $page->setIsSuccess($result['success']);
        $page->setItems($result['data']);

        return $page;
    }

    private function getCustomerNumber(string $customerNumber): string
    {
        return $this->apiConfig->getOrderTestModeOn() && $this->apiConfig->getOrderTestCustomerNumber() ? $this->apiConfig->getOrderTestCustomerNumber() : $customerNumber;
    }

    private function getBasicPage(Request $request, SalesChannelContext $context): Page
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException(404, '404', 'Customer not logged in');
        }

        $page = $this->genericPageLoader->load($request, $context);
        $page->getMetaInformation()?->setRobots('noindex,follow');

        return $page;
    }

    private function parseDocumentTypeEnum(string $documentType): string
    {
        if($documentType !== '0' && $documentType !== '1') {
            return '1';
        }

        return $documentType;
    }
}
