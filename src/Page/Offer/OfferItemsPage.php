<?php

declare(strict_types=1);

namespace StrackIntegrations\Page\Offer;

use Shopware\Storefront\Page\Page;

class OfferItemsPage extends Page
{
    protected bool $isSuccess;
    protected array $items;
    protected string $documentType; // 0: offer, 1: order

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): void
    {
        $this->documentType = $documentType;
    }
}
