<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Entity\Catalog;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Synchronizer\CatalogSynchronizer;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Gally Catalog data provider.
 */
class CatalogProvider
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
            $catalog = new Catalog('website_' . $website->getId(), $website->getName());
            foreach ($localizations as $localization) {
                yield new LocalizedCatalog(
                    $catalog,
                    'website_' . $website->getId() . '_' . $localization->getLanguageCode(),
                    $localization->getName(),
                    $localization->getFormattingCode(),
                    $this->currencyProvider->getWebsiteDefaultCurrency($website->getId())
                );
            }
        }
    }
}
