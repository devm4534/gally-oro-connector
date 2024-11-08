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

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractNormalizer
{
    public function preProcess(
        Website $website,
        Localization $localization,
        string $entityClass,
        array $entityConfig,
        array &$indexData,
    ): void {
    }

    public function normalize(
        Website $website,
        string $entityClass,
        string|int $entityId,
        array &$fieldsValues,
        array &$preparedEntityData,
    ): void {
    }

    public function postProcess(
        Website $website,
        string $entityClass,
        array &$preparedIndexData,
    ): void {
    }

    protected function toArray($value): array
    {
        if (\is_array($value) && !\array_key_exists('value', $value)) {
            return $value;
        }

        return [$value];
    }
}
