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

namespace Gally\OroPlugin\RequestBuilder;

use Gally\OroPlugin\Engine\ExpressionVisitor;
use Gally\Sdk\Entity\Catalog;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\GraphQl\Request;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class GallyRequestBuilder
{
    public function build(Query $query, array $context): Request
    {
        if (!$this->isProductQuery($query)) {
            // Todo !
            throw new \Exception('Todo manage this');
        }

        [$currentPage, $pageSize] = $this->getPaginationInfo($query);
        [$sortField, $sortDirection] = $this->getSortInfo($query);
        [$currentCategoryId, $searchQuery, $filters] = $this->getFilters($query);

        return new Request(
            $this->getCurrentLocalizedCatalog(),
            $this->getSelectedFields($query),
            $currentPage,
            $pageSize,
            $currentCategoryId,
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

    private function getCurrentLocalizedCatalog(): LocalizedCatalog
    {
        // Todo find context
        $catalog = new Catalog('website_1', 'Test');

        return new LocalizedCatalog(
            $catalog,
            //            'website_1_en_US',
            'website_1_fr_CA',
            'Blop',
            'en_US',
            'EUR'
        );
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
        $visitor = new ExpressionVisitor();
        $filters = [];

        if ($expression = $query->getCriteria()->getWhereExpression()) {
            $filters = $visitor->dispatch($expression);
        }

        return [$visitor->getCurrentCategoryId(), $visitor->getSearchQuery(), [$filters]];
    }
}
