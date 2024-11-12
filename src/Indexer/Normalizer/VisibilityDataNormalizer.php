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
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
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
            $visibleForCustomers = [];
            $hiddenForCustomers = [];
            $visibilities = $fieldsValues['visibility_customer.CUSTOMER_ID'] ?? [];
            foreach ($this->toArray($visibilities) as $value) {
                $value = $value['value'];
                $placeholders = [];

                if ($value instanceof PlaceholderValue) {
                    $placeholders = $value->getPlaceholders();
                    $value = $value->getValue();
                }

                if (BaseVisibilityResolved::VISIBILITY_VISIBLE === $value) {
                    $visibleForCustomers[] = $placeholders[CustomerIdPlaceholder::NAME];
                } elseif (BaseVisibilityResolved::VISIBILITY_HIDDEN === $value) {
                    $hiddenForCustomers[] = $placeholders[CustomerIdPlaceholder::NAME];
                }
            }

            if (!empty($visibleForCustomers)) {
                $preparedEntityData['visible_for_customer'] = $visibleForCustomers;
            }
            if (!empty($hiddenForCustomers)) {
                $preparedEntityData['hidden_for_customer'] = $hiddenForCustomers;
            }
            unset($fieldsValues['visibility_customer.CUSTOMER_ID']);
        }
    }
}
