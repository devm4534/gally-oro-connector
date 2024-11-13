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

namespace Gally\OroPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gally\OroPlugin\Engine\Indexer;
use Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListenerInterface;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Add child data to product data.
 */
class WebsiteSearchChildDataIndexerListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(
        private ManagerRegistry $doctrine,
        private EngineParameters $engineParameters,
    ) {
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (ElasticSearch::ENGINE_NAME === $this->engineParameters->getEngineName()) {
            return;
        }

        if (!$this->hasContextFieldGroup($event->getContext(), 'variant')) {
            return;
        }

        /** @var Localization $localization */
        $localization = $event->getContext()[Indexer::CONTEXT_LOCALIZATION];

        /** @var Product $product */
        $products = $event->getEntities();
        foreach ($products as $product) {
            if ($product->isConfigurable()) {
                foreach ($this->getVariants($product) as $variant) {
                    $event->addField($product->getId(), 'children.sku', $variant->getSku());
                    $event->addField($product->getId(), 'children.name', $variant->getName($localization)->getString());
                }
            }
        }
    }

    /**
     * @return Product[]
     */
    private function getVariants(Product $configurableProduct): array
    {
        $variantLinks = $configurableProduct->getVariantLinks();

        $variants = [];
        foreach ($variantLinks as $variantLink) {
            $variants[] = $variantLink->getProduct();
        }

        return $variants;
    }
}
