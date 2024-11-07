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

namespace Gally\OroPlugin\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolverInterface;

/**
 * Provides functionality to replace placeholders with their values in field names in all parts of a search query.
 * Todo useless ?
 */
class QueryPlaceholderResolver implements QueryPlaceholderResolverInterface
{
    public function __construct(
        private PlaceholderInterface $placeholder,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function replace(Query $query)
    {
        $this->replaceInSelect($query);
        $this->replaceInFrom($query);
        $this->replaceInCriteria($query);
        $this->replaceInAggregations($query);
    }

    private function replaceInSelect(Query $query)
    {
        $selectAliases = $query->getSelectAliases();
        $newSelects = [];
        foreach ($query->getSelect() as $select) {
            $newSelect = $this->placeholder->replaceDefault($select);
            if (isset($selectAliases[$select])) {
                $newSelect .= ' as ' . $selectAliases[$select];
            }

            $newSelects[] = $newSelect;
        }

        $query->select($newSelects);
    }

    /**
     * @return Query
     */
    private function replaceInFrom(Query $query)
    {
        $newEntities = [];
        $from = $query->getFrom();

        // This check required because getFrom can return false
        if ($from) {
            foreach ($from as $alias) {
                $newEntities[] = $this->placeholder->replaceDefault($alias);
            }
        }

        return $query->from($newEntities);
    }

    private function replaceInCriteria(Query $query)
    {
        $criteria = $query->getCriteria();
        $whereExpr = $criteria->getWhereExpression();

        if ($whereExpr) {
            $visitor = new PlaceholderExpressionVisitor($this->placeholder);
            $criteria->where($visitor->dispatch($whereExpr));
        }

        $orderings = $criteria->getOrderings();
        if ($orderings) {
            foreach ($orderings as $field => $ordering) {
                unset($orderings[$field]);
                $alteredField = $this->placeholder->replaceDefault($field);
                $orderings[$alteredField] = $ordering;
            }
            $criteria->orderBy($orderings);
        }
    }

    private function replaceInAggregations(Query $query)
    {
        $aggregations = $query->getAggregations();
        if (!$aggregations) {
            return;
        }

        $newAggregations = [];
        foreach ($aggregations as $name => $item) {
            $newAggregations[$name] = [
                'field' => $this->placeholder->replaceDefault($item['field']),
                'function' => $item['function'],
                'parameters' => $item['parameters'],
            ];
        }
        $query->setAggregations($newAggregations);
    }
}
