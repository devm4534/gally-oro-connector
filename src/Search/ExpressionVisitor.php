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
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;

class ExpressionVisitor extends BaseExpressionVisitor
{
    public function __construct(
        private array $attributeMapping,
    ) {
    }

    private ?string $searchQuery = null;

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
        $field = $this->attributeMapping[$field] ?? $field;
        $value = $this->dispatch($comparison->getValue());
        $operator = match ($comparison->getOperator()) {
            'IN', 'NOT IN' => Request::FILTER_OPERATOR_IN,
            'LIKE', 'NOT LIKE' => Request::FILTER_OPERATOR_MATCH,
            'EXISTS', 'NOT EXISTS' => Request::FILTER_OPERATOR_EXISTS,
            default => Request::FILTER_OPERATOR_EQ,
        };
        $hasNegation = str_starts_with($comparison->getOperator(), 'NOT');

        if ('all_text' === $field) {
            $this->searchQuery = $value;

            return null;
        }

        if ('id' === $field) {
            $type = 'text';
        } elseif ('inv_status' === $field) {
            $field = 'stock.status';
            if (\count($value) > 1) {
                return null; // if we want in stock and out of sotck product, we do not need this filter.
            }
            $operator = Request::FILTER_OPERATOR_EQ;
            $value = \in_array(Product::INVENTORY_STATUS_IN_STOCK, $value, true);
        } elseif (str_starts_with($field, 'assigned_to.') || str_starts_with($field, 'manually_added_to.')) {
            [$field, $variantId] = explode('.', $field);
            [$_, $value] = explode('_', $variantId);
        } elseif (str_starts_with($field, 'category_paths.')) {
            [$field, $value] = explode('.', $field);
            $operator = Request::FILTER_OPERATOR_IN;
        } elseif (str_starts_with($field, 'visibility_customer.')) {
            [$field, $customerId] = explode('.', $field);
            $type = 'text';
            if (Request::FILTER_OPERATOR_EXISTS === $operator) {
                $field = 'boolFilter';
                $operator = '_must';
                $value = [
                    ['visible_for_customer' => [Request::FILTER_OPERATOR_EQ => $customerId]],
                    ['hidden_for_customer' => [Request::FILTER_OPERATOR_EQ => $customerId]],
                ];
            } elseif (Request::FILTER_OPERATOR_EQ == $operator) {
                $field = BaseVisibilityResolved::VISIBILITY_HIDDEN === $value
                    ? 'hidden_for_customer'
                    : 'visible_for_customer';
                $value = $customerId;
            }
        }

        $rule = [$field => [$operator => $this->enforceValueType($type, $value)]];

        return $hasNegation
            ? ['boolFilter' => ['_not' => [$rule]]]
            : $rule;
    }

    public function walkValue(Value $value): mixed
    {
        return $value->getValue();
    }

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    private function enforceValueType(string $type, mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map(fn ($item) => $this->enforceValueType($type, $item), $value);
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'text' => (string) $value,
            default => $value,
        };
    }
}
