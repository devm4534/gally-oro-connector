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

namespace Gally\OroPlugin\Decorator;

use Oro\Bundle\ProductBundle\Search\ProductIndexAttributeProviderInterface;

class ProductIndexFieldsProvider implements ProductIndexAttributeProviderInterface
{
    public function __construct(
        private ProductIndexAttributeProviderInterface $productIndexAttributeProvider,
    ) {
    }

    public function addForceIndexed(string $field): void
    {
        $this->productIndexAttributeProvider->addForceIndexed($field);
    }

    public function isForceIndexed(string $field): bool
    {
        // Todo check search engine
        return true || $this->productIndexAttributeProvider->isForceIndexed($field);
    }
}
