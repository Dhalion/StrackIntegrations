<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Shopware\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class RegisterSubscriber implements EventSubscriberInterface
{
    public const B2B_CUSTOMER_DATA_KEY = 'b2bCustomerData';

    public function __construct(
        private EntityRepository $customerRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerRegisterEvent::class => 'onCustomerRegister',
            CustomerDoubleOptInRegistrationEvent::class => 'onCustomerRegister',
        ];
    }

    public function onCustomerRegister(CustomerRegisterEvent|CustomerDoubleOptInRegistrationEvent $event): void
    {
        $customerId = $event->getCustomerId();

        $b2bData = [
            'isDebtor' => true,
            'isSalesRepresentative' => false,
            'isInEasyMode' => true,
        ];

        $this->customerRepository->update([
            [
                'id' => $customerId,
                self::B2B_CUSTOMER_DATA_KEY => $b2bData,
            ],
        ], $event->getContext());

        $event->getCustomer()->setCustomFields(
            array_merge(
                $event->getCustomer()->getCustomFields() ?? [],
                $b2bData,
            )
        );
    }

}
