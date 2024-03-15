<?php

declare(strict_types=1);

namespace StrackIntegrations\Page\Order;

use Shopware\Storefront\Page\Page;

class OrderPage extends Page
{
    private const DEFAULT_PERIOD = '3 months ago';

    protected bool $isSuccess;
    protected array $orders;
    protected string $documentType; // 0: offer, 1: order
    protected ?\DateTimeImmutable $orderFrom = null;
    protected ?\DateTimeImmutable $orderTo = null;

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function setOrders(array $orders): void
    {
        $this->orders = $orders;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getOrderFrom(): \DateTimeImmutable
    {
        if ($this->orderFrom && $this->orderTo && $this->orderFrom > $this->orderTo) {
            return $this->orderTo;
        }

        return $this->orderFrom ?? (new \DateTimeImmutable())->modify(self::DEFAULT_PERIOD);
    }

    public function setOrderFrom(\DateTimeImmutable $orderFrom): void
    {
        $this->orderFrom = $orderFrom;
    }

    public function getOrderTo(): \DateTimeImmutable
    {
        if ($this->orderFrom && $this->orderTo && $this->orderFrom > $this->orderTo) {
            return $this->orderFrom;
        }

        return $this->orderTo ?? new \DateTimeImmutable();
    }

    public function setOrderTo(\DateTimeImmutable $orderTo): void
    {
        $this->orderTo = $orderTo;
    }
}
