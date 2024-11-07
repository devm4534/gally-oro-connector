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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor as BaseExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Gally\Sdk\GraphQl\Request;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class ExpressionVisitor extends BaseExpressionVisitor
{
    private ?string $searchQuery = null;
    private ?string $currentCategoryId = null;

    public function walkCompositeExpression(CompositeExpression $expr): array
    {
        $filters = [];
        foreach ($expr->getExpressionList() as $expression) {
            $filters[] = $this->dispatch($expression);
        }

        $type = CompositeExpression::TYPE_AND === $expr->getType() ? '_must' : '_should';

        return ['boolFilter' => [$type => array_values(array_filter($filters))]];
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
            return null;
        }

        if ('category_path' === $field) {
            $this->currentCategoryId = 'node_' . basename(str_replace('_', '/', $value));

            return null;
        }

        if (str_starts_with($field, 'assigned_to')) {
            return null; // Todo manage this
        }
        if (str_starts_with($field, 'manually_added_to')) {
            return null; // Todo manage this
        }

        if (str_starts_with($field, 'category_path')) {
            return null;
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
