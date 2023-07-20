<?php

declare(strict_types=1);

namespace StrackIntegrations\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BasketSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [

        ];
    }
}
