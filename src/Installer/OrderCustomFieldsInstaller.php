<?php

declare(strict_types=1);

namespace StrackIntegrations\Installer;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use StrackIntegrations\Util\CustomFieldsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class OrderCustomFieldsInstaller
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
                    'name' => CustomFieldsInterface::ORDER_CUSTOM_FIELD_SET,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Strack Bestellung',
                            'en-GB' => 'Strack Order'
                        ]
                    ],
                    "relations" => [
                        [
                            "id" => Uuid::randomHex(),
                            "entityName" => OrderDefinition::ENTITY_NAME
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => CustomFieldsInterface::ORDER_IS_OFFER,
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'customFieldPosition' => 100,
                                'label' => [
                                    'de-DE' => 'Ist Angebot?',
                                    'en-GB' => 'Is offer?'
                                ]
                            ]
                        ],
                        [
                            'name' => CustomFieldsInterface::ORDER_REQUESTED_DELIVERY_DATE,
                            'type' => CustomFieldTypes::DATE,
                            'config' => [
                                'customFieldPosition' => 200,
                                'label' => [
                                    'de-DE' => 'Wunschlieferdatum',
                                    'en-GB' => 'Requested delivery date'
                                ]
                            ]
                        ],
                        [
                            'name' => CustomFieldsInterface::ORDER_IS_PARTIAL_DELIVERY,
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'customFieldPosition' => 300,
                                'label' => [
                                    'de-DE' => 'Teillieferung',
                                    'en-GB' => 'Partial delivery'
                                ]
                            ]
                        ],
                        [
                            'name' => CustomFieldsInterface::ORDER_OWN_ORDER_NUMBER,
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'customFieldPosition' => 400,
                                'label' => [
                                    'de-DE' => 'Eigene Auftragsnummer',
                                    'en-GB' => 'Own order number'
                                ]
                            ]
                        ],
                        [
                            'name' => CustomFieldsInterface::ORDER_OFFER_NUMBER,
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'customFieldPosition' => 500,
                                'label' => [
                                    'de-DE' => 'Strack-Angebotsnummer',
                                    'en-GB' => 'Strack offer number'
                                ]
                            ]
                        ],
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
        $criteria->addFilter(new EqualsFilter('name', CustomFieldsInterface::ORDER_CUSTOM_FIELD_SET));

        return $customFieldSetRepository->searchIds($criteria, $context)->firstId();
    }
}
