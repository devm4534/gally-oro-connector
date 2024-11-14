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

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

/**
 * Driver for the partial update of the customer visibility in the website search index.
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
