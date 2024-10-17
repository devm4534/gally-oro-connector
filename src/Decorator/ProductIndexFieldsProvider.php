<?php

namespace Gally\OroPlugin\Decorator;

use Oro\Bundle\ProductBundle\Search\ProductIndexAttributeProviderInterface;

class ProductIndexFieldsProvider implements ProductIndexAttributeProviderInterface
{
    public function __construct(
        private ProductIndexAttributeProviderInterface $productIndexAttributeProvider
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
