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

namespace Gally\OroPlugin\Search;

use Gally\Sdk\GraphQl\Request;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class GallyRequestBuilder
{
    public function __construct(
        private ContextProvider $contextProvider,
        private ExpressionVisitor $expressionVisitor,
    ) {
    }

    public function build(Query $query, array $context): Request
    {
        if (!$this->isProductQuery($query)) {
            // Todo !
            throw new \Exception('Todo manage this');
        }

        [$currentPage, $pageSize] = $this->getPaginationInfo($query);
        [$sortField, $sortDirection] = $this->getSortInfo($query);
        [$searchQuery, $filters] = $this->getFilters($query);
        $currentContentNode = $this->contextProvider->getCurrentContentNode();

        return new Request(
            $this->contextProvider->getCurrentLocalizedCatalog(),
            $this->getSelectedFields($query),
            $currentPage,
            $pageSize,
            $currentContentNode ? (string) $currentContentNode->getId() : null,
            $searchQuery,
            $filters,
            $sortField,
            $sortDirection,
        );
    }

    private function isProductQuery(Query $query): bool
    {
        $from = $query->getFrom();

        return 1 === \count($from) && str_starts_with($from[0], 'oro_product');
    }

    private function getSelectedFields(Query $query): array
    {
        // Todo Clean field name
        $fields = $query->getSelect();
        $selectedFields = empty($fields) ? [] : ['id'];
        foreach ($fields as $field) {
            [$type, $name] = Criteria::explodeFieldTypeName($field);
            if ('names' === $name) {
                $name = 'name';
            }
            if ('system_entity_id' === $name) {
                $name = 'id';
            }
            if ('inv_status' == $name) { // todo
                continue;
            }
            $selectedFields[] = $name;
        }

        return $selectedFields;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getPaginationInfo(Query $query): array
    {
        $from = (int) $query->getCriteria()->getFirstResult();
        $pageSize = (int) $query->getCriteria()->getMaxResults() ?: 25;
        $currentPage = (int) ceil($from / $pageSize) + 1;

        return [$currentPage, $pageSize];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function getSortInfo(Query $query): array
    {
        $orders = $query->getCriteria()->getOrderings();

        if (!empty($orders)) {
            // We can only use one sort order in gally (score and id ordering are added automatically).
            $order = array_key_first($orders);
            [$type, $field] = Criteria::explodeFieldTypeName($order);

            if ('category_sort_order' == $field || str_starts_with($field, 'assigned_to_sort_order.')) {
                // todo manage this globally
                $field = 'category__position';
                $field = '_score';
            }

            return [$field, 'ASC' === $orders[$order] ? Request::SORT_DIRECTION_ASC : Request::SORT_DIRECTION_DESC];
        }

        return [null, null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: array}
     */
    private function getFilters(Query $query): array
    {
        $filters = [];

        if ($expression = $query->getCriteria()->getWhereExpression()) {
            $filters = $this->expressionVisitor->dispatch($expression);
        }

        return [$this->expressionVisitor->getSearchQuery(), [$filters]];
    }
}
