<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Gally\Sdk\Entity\Catalog;
use Gally\Sdk\Entity\LocalizedCatalog;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;

/**
 * Gally Catalog data provider.
 */
class CatalogProvider implements ProviderInterface
{
    protected WebsiteRepository $websiteRepository;
    protected AbstractWebsiteLocalizationProvider $websiteLocalizationProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        private WebsiteCurrencyProvider $currencyProvider,
    ) {
        $this->websiteRepository = $entityManager->getRepository(Website::class);
        $this->websiteLocalizationProvider = $websiteLocalizationProvider ;
    }
    /**
     * @return iterable<LocalizedCatalog>
     */
    public function provide(): iterable
    {
        $websites = $this->websiteRepository->findAll();

        foreach ($websites as $website) {
            $localizations = $this->websiteLocalizationProvider->getLocalizations($website);
            $catalog = new Catalog($this->getCatalogCodeFromWebsiteId($website->getId()), $website->getName());
            foreach ($localizations as $localization) {
                yield new LocalizedCatalog(
                    $catalog,
                    'website_' . $website->getId() . '_' . $localization->getFormattingCode(),
                    $localization->getName(),
                    $localization->getFormattingCode(),
                    $this->currencyProvider->getWebsiteDefaultCurrency($website->getId())
                );
            }
        }
    }

    public function getCatalogCodeFromWebsiteId(int $websiteId): string
    {
        return 'website_' . $websiteId;
    }
}
