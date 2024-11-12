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

namespace Gally\OroPlugin\Engine;

use Gally\OroPlugin\Registry\SearchRegistry;
use Gally\OroPlugin\Search\GallyRequestBuilder;
use Gally\Sdk\Service\SearchManager;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Gally website search engine.
 */
class SearchEngine extends AbstractEngine
{
    public const ENGINE_NAME = 'gally';

    protected Mapper $mapper;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueryPlaceholderResolverInterface $queryPlaceholderResolver,
        AbstractSearchMappingProvider $mappingProvider,
        private SearchManager $searchManager,
        private GallyRequestBuilder $requestBuilder,
        private SearchRegistry $registry,
        private array $attributeMapping,
    ) {
        parent::__construct($eventDispatcher, $queryPlaceholderResolver, $mappingProvider);
    }

    public function setMapper(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    protected function doSearch(Query $query, array $context = [])
    {
        $request = $this->requestBuilder->build($query, $context);
        $response = $this->searchManager->searchProduct($request);
        $this->registry->setResponse($response);

        $results = [];
        foreach ($response->getCollection() as $item) {
            $item['id'] = (int) basename($item['id']);

            foreach ($this->attributeMapping as $oroAttribute => $gallyAttribute) {
                $item[$oroAttribute] = $item[$gallyAttribute] ?? null;
            }

            $results[] = new Item(
                'product', // Todo manage other entity
                $item['id'],
                $item['url'] ?? null,
                $this->mapper->mapSelectedData($query, $item),
                $this->mappingProvider->getEntityConfig('product')
            );
        }

        $aggregations = [];
        foreach ($response->getAggregations() as $aggregation) {
            $field = $aggregation['field'];
            foreach ($aggregation['options'] as $option) {
                $aggregations[$field][$option['value']] = $option['count'];
            }
        }

        return new Result($query, $results, $response->getTotalCount(), $aggregations);
    }
}
