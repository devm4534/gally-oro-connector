<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2024-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\OroPlugin\Indexer;

use Gally\OroPlugin\Indexer\Provider\CatalogProvider;
use Gally\OroPlugin\Indexer\Provider\SourceFieldProvider;
use Gally\Sdk\Entity\Index;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Service\IndexOperation;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolverInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Indexer extends AbstractIndexer
{
    public const CONTEXT_LOCALIZATION = 'localization';

    /** @var LocalizedCatalog[][] */
    private array $localizedCatalogByWebsite;

    /** @var Index[] */
    private array $indicesByLocale;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        SearchMappingProvider $mappingProvider,
        EntityDependenciesResolverInterface $entityDependenciesResolver,
        IndexDataProvider $indexDataProvider,
        PlaceholderInterface $placeholder,
        IndexerInputValidator $indexerInputValidator,
        EventDispatcherInterface $eventDispatcher,
        PlaceholderInterface $regexPlaceholder,
        private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        private CatalogProvider $catalogProvider,
        private SourceFieldProvider $sourceFieldProvider,
        private IndexOperation $indexOperation,
    ) {
        parent::__construct(
            $doctrineHelper,
            $mappingProvider,
            $entityDependenciesResolver,
            $indexDataProvider,
            $placeholder,
            $indexerInputValidator,
            $eventDispatcher,
            $regexPlaceholder
        );
    }

    protected function reindexEntityClass($entityClass, array $context)
    {
        if ('\Oro\Bundle\WebsiteElasticSearchBundle\Entity\SavedSearch' === $entityClass) {
            // Todo managed savedSearch https://doc.oroinc.com/user/storefront/account/saved-search/
            return 0;
        }

        $websiteId = $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY];
        $metadata = $this->sourceFieldProvider->getMetadataFromEntityClass($entityClass);

        // Initialize indices list.
        $this->indicesByLocale = [];
        foreach ($this->getLocalizedCatalogByWebsite($websiteId) as $localizedCatalog) {
            if (empty($contextEntityIds)) { // Manage partial reindex todo
                $index = $this->indexOperation->createIndex($metadata, $localizedCatalog);
            } else {
                $index = $this->indexOperation->getIndexByName($metadata, $localizedCatalog);
            }
            $this->indicesByLocale[$localizedCatalog->getLocale()] = $index;
        }

        return parent::reindexEntityClass($entityClass, $context);
    }

    protected function indexEntities($entityClass, array $entityIds, array $context, $aliasToSave): array
    {
        $result = [];

        $websiteId = $context[self::CONTEXT_CURRENT_WEBSITE_ID_KEY];
        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        foreach ($localizations as $localization) {
            $context[self::CONTEXT_LOCALIZATION] = $localization;
            $result = parent::indexEntities($entityClass, $entityIds, $context, $aliasToSave);
        }

        return $result;
    }

    /**
     * @return LocalizedCatalog[]
     */
    private function getLocalizedCatalogByWebsite(int $websiteId): array
    {
        if (!isset($this->localizedCatalogByWebsite)) {
            foreach ($this->catalogProvider->provide() as $localizedCatalog) {
                $catalogCode = $localizedCatalog->getCatalog()->getCode();
                if (!isset($this->localizedCatalogByWebsite[$catalogCode])) {
                    $this->localizedCatalogByWebsite[$catalogCode] = [];
                }
                $this->localizedCatalogByWebsite[$catalogCode][] = $localizedCatalog;
            }
        }

        $catalogCode = $this->catalogProvider->getCatalogCodeFromWebsiteId($websiteId);

        return $this->localizedCatalogByWebsite[$catalogCode]; // todo if undefined ??
    }

    public function delete($entity, array $context = []): bool
    {
        // TODO: Implement delete() method.
        return true;
    }

    public function resetIndex($class = null, array $context = [])
    {
        // TODO: Implement resetIndex() method.
    }

    protected function saveIndexData($entityClass, array $entitiesData, $entityAliasTemp, array $context)
    {
        $realAlias = $this->getEntityAlias($entityClass, $context);

        if (null === $realAlias || empty($entitiesData)) {
            return [];
        }

        $bulk = array_map(fn ($data) => json_encode($data), $entitiesData);
        /** @var Localization $localization */
        $localization = $context[self::CONTEXT_LOCALIZATION];
        $index = $this->indicesByLocale[$localization->getFormattingCode()];
        $this->indexOperation->executeBulk($index, $bulk);

        return array_keys($entitiesData);
    }

    protected function savePartialIndexData($entityClass, array $entitiesData, $entityAliasTemp, array $context): array
    {
        // TODO: Implement savePartialIndexData() method.
        return [];
    }

    protected function getIndexedEntities($entityClass, array $entities, array $context): array
    {
        // TODO: Implement getIndexedEntities() method.
        return [];
    }

    protected function renameIndex($temporaryAlias, $currentAlias): void
    {
        foreach ($this->indicesByLocale as $index) {
            $this->indexOperation->refreshIndex($index);
            $this->indexOperation->installIndex($index);
        }
    }
}
