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

namespace Gally\OroPlugin\Resolver;

class PriceGroupResolver
{
    public function getGroupId(bool $isCPLUsed, int $priceListId, string $currency, ?string $unit): string
    {
        return implode(
            '_',
            array_filter([
                $isCPLUsed ? 'cpl' : 'pl',
                $priceListId,
                $currency,
                $unit,
            ])
        );
    }
}
