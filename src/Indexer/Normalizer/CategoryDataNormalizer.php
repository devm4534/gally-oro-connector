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

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

class CategoryDataNormalizer extends AbstractNormalizer
{
    private array $contentNodeData;

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private LocalizationHelper $localizationHelper,
    ) {
    }

    public function preProcess(
        Website $website,
        Localization $localization,
        string $entityClass,
        array $entityConfig,
        array &$indexData,
    ): void {
        $variantIds = [];
        foreach ($indexData as $fieldsValues) {
            $assignLinks = array_merge(
                $fieldsValues['assigned_to.ASSIGN_TYPE_ASSIGN_ID'] ?? [],
                $fieldsValues['manually_added_to.ASSIGN_TYPE_ASSIGN_ID'] ?? [],
            );
            foreach ($assignLinks as $assignLink) {
                $value = $assignLink['value'];
                if ($value instanceof PlaceholderValue) {
                    $placeholders = $value->getPlaceholders();
                    if ('variant' === $placeholders[AssignTypePlaceholder::NAME]) {
                        $variantId = $placeholders[AssignIdPlaceholder::NAME];
                        $variantIds[$variantId] = $variantId;
                    }
                }
            }
        }

        /** @var ContentVariantRepository $contentVariantRepository */
        $contentVariantRepository = $this->doctrineHelper->getEntityRepositoryForClass(ContentVariant::class);

        /** @var ContentVariant $variant */
        foreach ($contentVariantRepository->findBy(['id' => $variantIds]) as $variant) {
            $name = $this->localizationHelper->getLocalizedValue($variant->getNode()->getTitles(), $localization);
            $this->contentNodeData[$variant->getId()] = [
                'id' => $variant->getNode()->getId(),
                'name' => $name->getString(),
            ];
        }
    }

    public function normalize(
        Website $website,
        string $entityClass,
        string|int $entityId,
        array &$fieldsValues,
        array &$preparedEntityData,
    ): void {
        $categories = [];
        $assignLinks = array_merge(
            $fieldsValues['assigned_to.ASSIGN_TYPE_ASSIGN_ID'] ?? [],
            $fieldsValues['manually_added_to.ASSIGN_TYPE_ASSIGN_ID'] ?? [],
        );
        foreach ($assignLinks as $assignLink) {
            $value = $assignLink['value'];
            if ($value instanceof PlaceholderValue) {
                $placeholders = $value->getPlaceholders();
                if ('variant' === $placeholders[AssignTypePlaceholder::NAME]) {
                    $variantId = $placeholders[AssignIdPlaceholder::NAME];
                    $categories[$variantId] = [
                        'id' => $this->contentNodeData[$variantId]['id'],
                        'name' => $this->contentNodeData[$variantId]['name'],
                    ];
                }
            }
        }

        $assignSortOrders = $fieldsValues['assigned_to_sort_order.ASSIGN_TYPE_ASSIGN_ID'] ?? [];
        foreach ($assignSortOrders as $assignSortOrder) {
            $value = $assignSortOrder['value'];
            if ($value instanceof PlaceholderValue) {
                $placeholders = $value->getPlaceholders();
                if ('variant' === $placeholders[AssignTypePlaceholder::NAME]) {
                    $variantId = $placeholders[AssignIdPlaceholder::NAME];
                    $categories[$variantId]['position'] = $value->getValue();
                }
            }
        }

        if (!empty($categories)) {
            $preparedEntityData['assigned_to'] = array_keys($categories);
            $preparedEntityData['category'] = array_values($categories);
        }

        // category_id
        // category_path
        // category_paths.CATEGORY_PATH
        // category_title_LOCALIZATION_ID
        // category_id_with_parent_categories_LOCALIZATION_ID (12|Retail Supplies)
        // category_sort_order
        unset($fieldsValues['assigned_to.ASSIGN_TYPE_ASSIGN_ID']);
        unset($fieldsValues['manually_added_to.ASSIGN_TYPE_ASSIGN_ID']);
        unset($fieldsValues['assigned_to_sort_order.ASSIGN_TYPE_ASSIGN_ID']);
    }
}
