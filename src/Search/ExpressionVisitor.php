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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor as BaseExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Gally\Sdk\GraphQl\Request;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class ExpressionVisitor extends BaseExpressionVisitor
{
    private ?string $searchQuery = null;
    private ?string $currentCategoryId = null;

    public function dispatch(Expression $expr, bool $isMainQuery = true)
    {
        // Use main query parameter to flatten main and expression.

        switch (true) {
            case $expr instanceof Comparison:
                return $this->walkComparison($expr);
            case $expr instanceof Value:
                return $this->walkValue($expr);
            case $expr instanceof CompositeExpression:
                return $this->walkCompositeExpression($expr, $isMainQuery);
            default:
                throw new \RuntimeException('Unknown Expression ' . $expr::class);
        }
    }

    public function walkCompositeExpression(CompositeExpression $expr, bool $isMainQuery = true): array
    {
        $type = '_must';
        if (CompositeExpression::TYPE_AND !== $expr->getType()) {
            $isMainQuery = false;
            $type = '_should';
        }

        $filters = [];
        foreach ($expr->getExpressionList() as $expression) {
            $filters[] = $this->dispatch($expression, $isMainQuery);
        }
        $filters = array_values(array_filter($filters));

        return $isMainQuery
            ? array_merge(...$filters)
            : ['boolFilter' => [$type => $filters]];
    }

    public function walkComparison(Comparison $comparison): ?array
    {
        [$type, $field] = Criteria::explodeFieldTypeName($comparison->getField());
        $value = $this->dispatch($comparison->getValue());
        $operator = match ($comparison->getOperator()) {
            'IN' => Request::FILTER_OPERATOR_IN,
            'LIKE' => Request::FILTER_OPERATOR_MATCH,
            default => Request::FILTER_OPERATOR_EQ,
            // todo add EXISTS
        };

        if ('all_text' === $field) {
            $this->searchQuery = $value;

            return null;
        }

        if ('inv_status' === $field) {
            $field = 'stock.status';
            if (count($value) > 1) {
                return null; // if we want in stock and out of sotck product, we do not need this filter.
            }
            $operator = Request::FILTER_OPERATOR_EQ;
            $value = in_array(\Oro\Bundle\ProductBundle\Entity\Product::INVENTORY_STATUS_IN_STOCK, $value, true);
            //            return null; //todo mange specificque code for code stock
        } elseif (str_starts_with($field, 'visibility_customer.')) {
            [$field, $value] = explode('.', $field);
        }

        if ('category_path' === $field) {
            //            $this->currentCategoryId = 'node_' . basename(str_replace('_', '/', $value)); // todo this is wrong, the current category should contain content node id !

            //            return null;
        }

        if (str_starts_with($field, 'assigned_to')) {
            return null; // Todo manage this
        }
        if (str_starts_with($field, 'manually_added_to')) {
            return null; // Todo manage this
        }

        if (str_starts_with($field, 'category_path')) {
            //            return null;
        }

        return [$field => [$operator => $value]];
    }

    public function walkValue(Value $value): mixed
    {
        return $value->getValue();
    }

    public function getCurrentCategoryId(): ?string
    {
        return $this->currentCategoryId;
    }

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }
}
