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

namespace Gally\OroPlugin\Indexer\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class StockDataNormalizer extends AbstractNormalizer
{
    public function normalize(
        Website $website,
        string $entityClass,
        string|int $entityId,
        array &$fieldsValues,
        array &$preparedEntityData,
    ): void {
        if (Product::class == $entityClass) {
            $status = reset($fieldsValues['inv_status'])['value'];
            $qty = reset($fieldsValues['inv_qty'])['value'];
            $preparedEntityData['stock'] = [
                'status' => Product::INVENTORY_STATUS_IN_STOCK == $status,
                'qty' => $qty ?? 0,
            ];
            unset($fieldsValues['inv_status']);
            unset($fieldsValues['inv_qty']);
        }
    }
}
