<?php

declare(strict_types=1);

namespace StrackIntegrations\B2bSuite\Shop\BridgePlatform;

use Doctrine\DBAL\Connection;
use Shopware\B2B\LineItemList\Framework\LineItemReference;
use Shopware\B2B\Shop\BridgePlatform\ContextProvider;
use Shopware\B2B\Shop\BridgePlatform\TranslatedFieldQueryExtenderTrait;
use Shopware\B2B\Shop\Framework\ProductServiceInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

readonly class ProductService implements ProductServiceInterface
{
    use TranslatedFieldQueryExtenderTrait;

    public function __construct(
        private ProductServiceInterface $decorated,
        private ContextProvider $contextProvider,
        private Connection $connection,
    ) {
    }

    public function fetchProductNameByOrderNumber(string $orderNumber): string
    {
        return $this->decorated->fetchProductNameByOrderNumber($orderNumber);
    }

    public function fetchProductNamesByOrderNumbers(array $orderNumbers): array
    {
        return $this->decorated->fetchProductNamesByOrderNumbers($orderNumbers);
    }

    public function fetchOrderNumberByReferenceNumber(string $referenceNumber): string
    {
        return $this->decorated->fetchOrderNumberByReferenceNumber($referenceNumber);
    }

    public function fetchProductOrderNumbersByReferenceNumbers(array $referenceNumbers): array
    {
        return $this->decorated->fetchProductOrderNumbersByReferenceNumbers($referenceNumbers);
    }

    public function searchProductsByNameOrOrderNumber(string $term, int $limit): array
    {
        return $this->decorated->searchProductsByNameOrOrderNumber($term, $limit);
    }

    public function fetchStocksByOrderNumbers(array $orderNumbers): array
    {
        return $this->decorated->fetchStocksByOrderNumbers($orderNumbers);
    }

    public function isNormalProduct(LineItemReference $reference): bool
    {
        return $this->decorated->isNormalProduct($reference);
    }

    public function fetchProductInformationByOrderNumbers(array $productOrderNumbers): array
    {
        $context = $this->contextProvider->getSalesChannelContext();
        //possible working query
        $query = $this->connection->createQueryBuilder()
            ->from('product')
            ->select(
                'product.product_number',
                'IFNULL(product_name, parent_name) as name',
                'IFNULL(product.min_purchase, parent.min_purchase) as min_purchase',
                'IFNULL(product.max_purchase, parent.max_purchase) as max_purchase',
                'IFNULL(product.purchase_steps, parent.purchase_steps) as purchase_steps',
                'JSON_UNQUOTE(JSON_EXTRACT(productTranslation.custom_fields, "$.strack_bestellschluessel_code")) as strack_bestellschluessel_code'
            )
            ->leftJoin('product', 'product', 'parent', 'product.parent_id = parent.id AND product.version_id = parent.version_id')
            ->leftJoin('product', 'b2b_order_number', 'b2bOrderNumber', 'product.id = b2bOrderNumber.product_id AND product.version_id = b2bOrderNumber.product_version_id')
            ->leftJoin('product', 'product_option', 'productOption', 'product.id = productOption.product_id AND product.version_id = productOption.product_version_id')
            ->leftJoin('productOption', 'property_group_option', 'propertyGroupOption', 'productOption.property_group_option_id = propertyGroupOption.id')
            ->leftJoin('product', 'product_translation', 'productTranslation', 'product.id = productTranslation.product_id AND product.version_id = productTranslation.product_version_id AND productTranslation.language_id = :languageId')
            ->where('product.product_number IN (:productNumbers) OR b2bOrderNumber.custom_ordernumber IN (:productNumbers) OR (JSON_UNQUOTE(JSON_EXTRACT(productTranslation.custom_fields, "$.strack_bestellschluessel_code")) IN (:productNumbers) AND product.parent_id IS NOT NULL)')
            ->andWhere('IFNULL(product.active, parent.active) = 1')
            ->andWhere('product.version_id = :versionId')
            ->setParameter('productNumbers', $productOrderNumbers, Connection::PARAM_STR_ARRAY)
            ->setParameter('versionId', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setParameter('languageId', Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM));

        $this->addTranslatedFieldSelect(
            $query,
            'name',
            'product_name',
            'product_translation',
            'product',
            'product',
            'id',
            $context,
            true,
            false
        );

        $this->addTranslatedFieldSelect(
            $query,
            'name',
            'parent_name',
            'product_translation',
            'product',
            'parent',
            'id',
            $context,
            true,
            false
        );

        $this->addTranslatedFieldSelect(
            $query,
            'name',
            'options',
            'property_group_option_translation',
            'property_group_option',
            'propertyGroupOption',
            'id',
            $context,
            false,
            true
        );

        $query->addGroupBy('product.product_number');
        $products = $query->fetchAllAssociative();

        $productsData = [];

        foreach ($products as $product) {
            $productsData[$product['product_number']] = [
                'name' => $product['name'],
                'minPurchase' => $product['min_purchase'],
                'maxPurchase' => $product['max_purchase'],
                'purchaseStep' => $product['purchase_steps'],
                'productNumber' => $product['product_number'],
                'strack_bestellschluessel_code' => $product['strack_bestellschluessel_code']
            ];
        }

        return $productsData;
    }
}
