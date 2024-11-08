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

use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

class VisibilityDataNormalizer extends AbstractNormalizer
{
    public function __construct(
    ) {
    }

    public function normalize(
        Website $website,
        string $entityClass,
        string|int $entityId,
        array &$fieldsValues,
        array &$preparedEntityData,
    ): void {
        if (Product::class === $entityClass) {
            $visibilitiesByCustomer = [];
            $visibilities = $fieldsValues['visibility_customer.CUSTOMER_ID'] ?? [];
            foreach ($this->toArray($visibilities) as $value) {
                $value = $value['value'];
                $placeholders = [];

                if ($value instanceof PlaceholderValue) {
                    $placeholders = $value->getPlaceholders();
                    $value = $value->getValue();
                }

                $visibilitiesByCustomer[] = [
                    'customer_id' => $placeholders[CustomerIdPlaceholder::NAME],
                    'value' => $value,
                ];
            }

            if (!empty($visibilitiesByCustomer)) {
                $preparedEntityData['visibility_customer'] = $visibilitiesByCustomer;
            }
            unset($fieldsValues['visibility_customer.CUSTOMER_ID']);
        }
    }
}
