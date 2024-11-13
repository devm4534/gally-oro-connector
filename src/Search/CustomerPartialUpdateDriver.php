<?php

namespace Gally\OroPlugin\Search;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteElasticSearchBundle\Manager\ElasticSearchPartialUpdateManager;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

/**
 * Driver for the partial update of the customer visibility in the website search index
 */
class CustomerPartialUpdateDriver extends AbstractCustomerPartialUpdateDriver
{
    public function __construct(
        PlaceholderProvider $placeholderProvider,
        ProductVisibilityProvider $productVisibilityProvider,
        DoctrineHelper $doctrineHelper,
    ) {
        parent::__construct($placeholderProvider, $productVisibilityProvider, $doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomerWithoutCustomerGroupVisibility(Customer $customer)
    {
        // Todo manage partial update
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCustomerVisibility(Customer $customer)
    {
        // Todo manage partial update
    }

    /**
     * {@inheritdoc}
     */
    protected function addCustomerVisibility(array $productIds, $productAlias, $customerVisibilityFieldName)
    {
        // Todo manage partial update
    }
}
