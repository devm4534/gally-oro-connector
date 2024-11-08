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

use Gally\OroPlugin\Engine\SearchEngine;
use Oro\Bundle\ProductBundle\Search\ProductIndexAttributeProviderInterface;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;

class ProductIndexFieldsProvider implements ProductIndexAttributeProviderInterface
{
    public function __construct(
        private ProductIndexAttributeProviderInterface $productIndexAttributeProvider,
        private EngineParameters $engineParameters,
    ) {
    }

    public function addForceIndexed(string $field): void
    {
        $this->productIndexAttributeProvider->addForceIndexed($field);
    }

    public function isForceIndexed(string $field): bool
    {
        return SearchEngine::ENGINE_NAME === $this->engineParameters->getEngineName()
            || $this->productIndexAttributeProvider->isForceIndexed($field);
    }
}
