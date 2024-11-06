<?php

namespace Gally\OroPlugin\Filter;

use Gally\OroPlugin\Form\SelectFormFilter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\BaseMultiChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by a multi-enum entity for a datasource based on a search index.
 */
class SelectFilter extends BaseMultiChoiceFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        parent::init($name, $params);

        $this->params[FilterUtility::FRONTEND_TYPE_KEY] = 'multiselect';
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $criteria = Criteria::create();
        $builder = Criteria::expr();

        $criteria->where($builder->in($fieldName, $data['value']));
        $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return SelectFormFilter::class;
    }
}
