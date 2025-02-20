<?php

declare(strict_types=1);

namespace StrackIntegrations\Installer;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class CustomerCustomFieldsInstaller
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function installCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $fieldSetId = $this->getCustomFieldSetId($context);

        if (!$fieldSetId) {
            $customFieldSetRepository->create([
                [
                    'name' => CustomFieldsInterface::CUSTOMER_CUSTOM_FIELD_SET,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Strack Kunde',
                            'en-GB' => 'Strack Customer'
                        ]
                    ],
                    "relations" => [
                        [
                            "id" => Uuid::randomHex(),
                            "entityName" => CustomerDefinition::ENTITY_NAME
                        ]
                    ],
                    'customFields' => [
                         [
                            'name' => CustomFieldsInterface::CUSTOMER_MINIMUM_ORDER_VALUE,
                            'type' => CustomFieldTypes::FLOAT,
                            'config' => [
                                'customFieldPosition' => 200,
                                'label' => [
                                    'de-DE' => 'Mindestbestellwert',
                                    'en-GB' => 'Minimum order value'
                                ]
                            ]
                        ]
                    ]
                ]
            ], $context);
        }

    }

    public function uninstallCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $fieldSetId = $this->getCustomFieldSetId($context);
        if ($fieldSetId) {
            $customFieldSetRepository->delete([['id' => $fieldSetId]], $context);
        }
    }

    private function getCustomFieldSetId(Context $context): ?string
    {
        /** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', CustomFieldsInterface::CUSTOMER_CUSTOM_FIELD_SET));

        return $customFieldSetRepository->searchIds($criteria, $context)->firstId();
    }
}
