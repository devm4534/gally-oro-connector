<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Engine;

use Gally\OroPlugin\Provider\CatalogProvider;
use Gally\OroPlugin\Provider\SourceFieldProvider;
use Gally\Sdk\Entity\Index;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Service\IndexOperation;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolverInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GallyIndexer extends AbstractIndexer
{
    private const CONTEXT_LOCALE_ID = 'localeId';
    private const CONTEXT_LOCALE_CODE = 'localeCode';

    /** @var LocalizedCatalog[] */
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
        $websiteId = $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY];
        $metadata = $this->sourceFieldProvider->getMetadataFromEntityClass($entityClass);

        // Initialize indices list.
        $this->indicesByLocale = [];
        foreach ($this->getLocalizedCatalogByWebsite($websiteId) as $localizedCatalog) {
            if (empty($contextEntityIds)) {
                $index = $this->indexOperation->createIndex($metadata, $localizedCatalog);
            } else {
                $index = $this->indexOperation->getIndexByName($metadata, $localizedCatalog);
            }
            $this->indicesByLocale[$localizedCatalog->getLocale()] = $index;
        }

        return parent::reindexEntityClass($entityClass, $context);

//        // Create entity iterator
//        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
//        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);
//        $queryBuilder = $entityRepository->createQueryBuilder('entity');
//        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
//        $queryBuilder->select("entity.$identifierName as id");
//        $contextEntityIds = $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] ?? [];
//        if ($contextEntityIds) {
//            $queryBuilder->where($queryBuilder->expr()->in("entity.$identifierName", ':contextEntityIds'))
//                ->setParameter('contextEntityIds', array_values($contextEntityIds));
//        }
//        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
//        $iterator->setBufferSize($this->getBatchSize());
//
//        // Bulk data in each indices
//        $itemsCount = 0;
//        $entityIds = [];
//        $indexedContextEntityIds = [];
//        $indexedItemsNum = 0;
//        // $batchSize = $this->configuration->getBatchSize($metadata, $localizedCatalog); Todo manage batch size config
//        $batchSize = 10;
//        foreach ($iterator as $entity) {
//            $entityIds[] = $entity['id'];
//            $itemsCount++;
//            if (\count($entityIds) >= $batchSize) {
//                $indexedEntityIds = $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
//                $indexedItemsNum += count($indexedEntityIds);
//                if ($contextEntityIds) {
//                    $indexedContextEntityIds = array_merge($indexedContextEntityIds, $indexedEntityIds);
//                }
////                $this->indexOperation->executeBulk($index, $bulk);
//                $entityIds = [];
//                $entityManager->clear($entityClass);
//            }
//        }
//
//        if ($itemsCount % $this->getBatchSize() > 0) {
//            $indexedEntityIds = $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
//            $indexedItemsNum += count($indexedEntityIds);
//            if ($contextEntityIds) {
//                $indexedContextEntityIds = array_merge($indexedContextEntityIds, $indexedEntityIds);
//            }
////            $this->indexOperation->executeBulk($index, $bulk);
//            $entityManager->clear($entityClass);
//        }
//
//        if ($contextEntityIds) {
//            // Todo manage deleted item on partial reindex ?
////            $removedContextEntityIds = array_diff($contextEntityIds, $indexedContextEntityIds);
////            if ($removedContextEntityIds) {
////                $this->deleteEntities($entityClass, $removedContextEntityIds, $context);
////            }
//        } else {
//            $this->indexOperation->refreshIndex($index);
//            $this->indexOperation->installIndex($index);
//        }
//
//        $afterReindexEvent = new AfterReindexEvent(
//            $entityClass,
//            $context,
//            $indexedContextEntityIds,
//            $removedContextEntityIds ?? []
//        );
//        $this->eventDispatcher->dispatch($afterReindexEvent, AfterReindexEvent::EVENT_NAME);
//
//        return $indexedItemsNum;
    }

    protected function indexEntities($entityClass, array $entityIds, array $context, $aliasToSave): array
    {
        $result = [];

        $websiteId = $context[self::CONTEXT_CURRENT_WEBSITE_ID_KEY];
        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        foreach ($localizations as $localization) {
            $context[self::CONTEXT_LOCALE_ID] = $localization->getId();
            $context[self::CONTEXT_LOCALE_CODE] = $localization->getFormattingCode();
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
        return $this->localizedCatalogByWebsite[$catalogCode]; //todo if undefined ??
    }



//    public function save($entity, array $context = [])
//    {
//        $toto = 'blop';
//        // TODO: Implement save() method.
//    }

    public function delete($entity, array $context = [])
    {
        $toto = 'blop';
        // TODO: Implement delete() method.
    }

//    public function getClassesForReindex($class = null, array $context = [])
//    {
//        $toto = 'blop';
//        // TODO: Implement getClassesForReindex() method.
//    }

    public function resetIndex($class = null, array $context = [])
    {
        $toto = 'blop';
        // TODO: Implement resetIndex() method.
    }

//    public function reindex($class = null, array $context = [])
//    {
//        $toto = 'blop';
//        // TODO: Implement reindex() method.
//    }

    protected function saveIndexData($entityClass, array $entitiesData, $entityAliasTemp, array $context)
    {
        // TODO: Implement saveIndexData() method.

//        $realAlias = $this->getEntityAlias($entityClass, $context);
//
//        if (null === $realAlias || empty($entitiesData)) {
//            return [];
//        }
//
//        $body = [];
//
//        $indexName = $this->indexAgent->getIndexNameByAlias($realAlias);
//
//        foreach ($entitiesData as $entityId => $entityData) {
//            $indexIdentifier = ['_id' => $entityId];
//
//            $indexData = $this->prepareIndexData($entityData);
//            if ($indexData) {
//                $indexData[IndexAgent::TMP_ALIAS_FIELD] = $entityAliasTemp;
//
//                if ($isUpdate) {
//                    $indexIdentifier['retry_on_conflict'] = 10;
//                    $entityData['update'][IndexAgent::TMP_ALIAS_FIELD] = $entityAliasTemp;
//                    $this->addUpdateInstructions($entityData, $indexIdentifier, $body);
//                } else {
//                    $body[] = ['index' => $indexIdentifier];
//                    $body[] = $indexData;
//                }
//            } elseif (!$isUpdate) {
//                $body[] = ['delete' => $indexIdentifier];
//            }
//        }
//
//        if ($body) {
//            $preparedRequest = $this->indexAgent->prepareDataForRequest([
//                'index' => $indexName,
//                'body' => $body
//            ]);
//
//            $response = $this->indexAgent->getClient()->bulk($preparedRequest)->asArray();
//
//            if ($response['errors']) {
//                $this->logger->debug('elk prepared request = {request}', ['request' => $preparedRequest]);
//                $this->logErrors($response, $indexName);
//                throw new \RuntimeException('Reindex failed');
//            }
//        }

        return array_keys($entitiesData);
    }

    protected function savePartialIndexData($entityClass, array $entitiesData, $entityAliasTemp, array $context)
    {
        $toto = 'blop';
        // TODO: Implement savePartialIndexData() method.
    }

    protected function getIndexedEntities($entityClass, array $entities, array $context)
    {
        $toto = 'blop';
        // TODO: Implement getIndexedEntities() method.
    }

    protected function renameIndex($temporaryAlias, $currentAlias): void
    {
        foreach ($this->indicesByLocalizedCatalog as $index) {
            $this->indexOperation->refreshIndex($index);
            $this->indexOperation->installIndex($index);
        }
    }
}
