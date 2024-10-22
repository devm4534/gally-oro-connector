<?php

namespace Gally\OroPlugin\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListenerInterface;
use Oro\Bundle\WarehouseBundle\Provider\EnabledWarehousesProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add stock quantity to product data.
 */
class WebsiteSearchInventoryLevelIndexerListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    public function __construct(
        private ManagerRegistry $doctrine,
        private EnabledWarehousesProvider $enabledWarehousesProvider,
    ) {
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'inventory')) {
            return;
        }

        $stockLevels = [];
        $inventoryLevelRepository = $this->doctrine->getRepository(InventoryLevel::class);
        $inventoryLevels = $inventoryLevelRepository->findBy([
            'product' => $event->getEntities(),
            'warehouse' => $this->enabledWarehousesProvider->getEnabledWarehouseIds()
        ]);

        /** @var InventoryLevel[] $inventoryLevel */
        foreach ($inventoryLevels as $inventoryLevel) {
            $product = $inventoryLevel->getProduct();
            if ($inventoryLevel->getProductUnitPrecision() !== $inventoryLevel->getProduct()->getPrimaryUnitPrecision()) {
                continue;
            }

            if (!array_key_exists($product->getId(), $stockLevels)) {
                $stockLevels[$product->getId()] = 0;
            }

            $stockLevels[$product->getId()] += $inventoryLevel->getQuantity();
        }

        foreach ($stockLevels as $entityId => $stockLevel) {
            $event->addField($entityId, 'inv_qty', $stockLevel);
        }
    }
}
