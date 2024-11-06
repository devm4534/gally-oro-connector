<?php

namespace Gally\OroPlugin\Registry;

use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\GraphQl\Response;
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
    private Response $response;

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
