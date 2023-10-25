<?php declare(strict_types=1);

namespace StrackIntegrations\Core\Framework\Adapter\Twig\Filter;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Page;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrencyFilter extends \Shopware\Core\Framework\Adapter\Twig\Filter\CurrencyFilter
{

    public function __construct(
        private readonly CurrencyFormatter   $currencyFormatter,
        private readonly TranslatorInterface $translator
    )
    {
        parent::__construct($this->currencyFormatter);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function formatCurrency($twigContext, $price, $currencyIsoCode = null, $languageId = null, ?int $decimals = null): string
    {
        // Only affect saleschannel
        if (array_key_exists('context', $twigContext) && $twigContext['context'] instanceof SalesChannelContext) {
            $salesChannelContext = $twigContext['context'];
            $twigCustomer = $twigContext['customer'] ?? null;
            $userLoggedIn = $salesChannelContext->getCustomer() || $twigCustomer;

            // The status for the price-request is not available everywhere, where a price is displayed, thats why we have to assume it is successful, until it expliclity wasn't.
            $priceRequestSuccessful = true;

            // Location PDP
            if (array_key_exists('page', $twigContext) && $twigContext['page'] instanceof Page && property_exists($twigContext['page'], 'customerPriceError')) {
                $priceRequestSuccessful = false;
            }

            // Location Cart
            if (array_key_exists('lineItem', $twigContext) && method_exists($twigContext['lineItem'], 'getPayload') && array_key_exists('customerPriceError', $twigContext['lineItem']->getPayload())) {
                $priceRequestSuccessful = false;
            }

            if (!$userLoggedIn || !$priceRequestSuccessful || $price == 0) {
                return $this->translator->trans("StrackIntegrations.placeholder.priceOnlyOnRequest");
            }
        }

        return parent::formatCurrency($twigContext, $price, $currencyIsoCode, $languageId, $decimals);
    }
}
