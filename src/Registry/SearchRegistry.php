<?php

namespace Gally\OroPlugin\Registry;

use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Service\SearchManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * Search registry.
 */
class SearchRegistry
{
    private array $aggregations;

    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function setAggregations(array $aggregations): void
    {
        $this->aggregations = $aggregations;
    }
}
