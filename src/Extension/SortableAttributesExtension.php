<?php

namespace Gally\OroPlugin\Extension;

use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Service\SearchManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * Get sortable field from Gally api.
 */
class SortableAttributesExtension extends AbstractExtension
{
    public function __construct(
        private SearchManager $searchManager
    ) {
    }

    public function isApplicable(DatagridConfiguration $config): bool
    {
        return $config->getName() === 'frontend-product-search-grid';
    }

    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $sortableAttributes = $this->searchManager->getProductSortingOptions();

        /** @var SourceField[] $sorters */
        $sorters = [];
        $config->offsetSetByPath('[sorters][columns]', []);
        foreach ($sortableAttributes as $attribute) {
            $sorters[$attribute->getCode()] = $attribute;
            $config->offsetSetByPath(
                '[sorters][columns][' . $attribute->getCode() . ']',
                ['data_name' => $attribute->getCode()]
            );
        }

        $proceed = [];
        foreach ($config->offsetGetOr('columns', []) as $name => $column) {
            if (array_key_exists($name, $sorters)) {
                $proceed[] = $name;
            }
        }

        $columnToAdd = array_diff(array_keys($sorters), $proceed);
        foreach($columnToAdd as $attributeCode) {
            $attribute = $sorters[$attributeCode];
            $columnData = [
                'label' => $attribute->getDefaultLabel(),
                'type' => 'field',
                'frontend_type' => 'string',
                'translatable' => false,
                'editable' => false,
                'shortenableLabel' => true,
            ];
            $config->offsetAddToArrayByPath('[columns]', [$attribute->getCode() => $columnData]);
        }
    }

    public function getPriority()
    {
        return -255;
    }
}
