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

namespace Gally\OroPlugin\Engine;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Placeholder\CPLIdPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\PriceListIdPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider as BaseIndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class is responsible for triggering all events during indexation
 * and returning all collected and prepared for saving event data.
 */
class IndexDataProvider extends BaseIndexDataProvider
{
    use ContextTrait;

    // Todo get from conf
    protected array $attributeCodeMapping = [
        'names' => 'name',
        'descriptions' => 'description',
    ];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private EntityAliasResolver $entityAliasResolver,
        private PlaceholderInterface $placeholder,
        HtmlTagHelper $htmlTagHelper,
        PlaceholderHelper $placeholderHelper,
        private DoctrineHelper $doctrineHelper,
        private LocalizationHelper $localizationHelper,
        private WebsiteCurrencyProvider $currencyProvider,
        private WebsiteContextManager $websiteContextManager,
        private ConfigManager $configManager,
        private FeatureChecker $featureChecker,
        private SearchMappingProvider $mappingProvider,
        private EntityManagerInterface $entityManager,
        private EnumTypeHelper $enumTypeHelper,
    ) {
        parent::__construct($eventDispatcher, $entityAliasResolver, $placeholder, $htmlTagHelper, $placeholderHelper);
    }

    /**
     * @param string   $entityClass
     * @param object[] $restrictedEntities
     * @param array    $context
     *                                     $context = [
     *                                     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     *                                     ]
     *
     * @return array
     */
    public function getEntitiesData($entityClass, array $restrictedEntities, array $context, array $entityConfig)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $indexEntityEvent = new Event\IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch($indexEntityEvent, Event\IndexEntityEvent::NAME);
        $this->eventDispatcher->dispatch(
            $indexEntityEvent,
            \sprintf('%s.%s', Event\IndexEntityEvent::NAME, $entityAlias)
        );

        return $this->prepareIndexData($entityClass, $indexEntityEvent->getEntitiesData(), $entityConfig, $context);
    }

    /**
     * Adds field types according to entity config, applies placeholders.
     *
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(string $entityClass, array $indexData, array $entityConfig, array $context): array
    {
        $preparedIndexData = [];
        $nodeIds = [];

        /** @var Localization $localization */
        $localization = $context[Indexer::CONTEXT_LOCALIZATION];
        $website = $this->websiteContextManager->getWebsite($context);
        $defaultCurrency = $this->currencyProvider->getWebsiteDefaultCurrency($website->getId());
        $defaultPriceList = $this->getDefaultPriceListForWebsite($website);
        $optionValues = $this->prepareOptionValues($entityClass, $indexData, $localization);

        // Todo brand / variant

        foreach ($indexData as $entityId => $fieldsValues) {
            $categories = [];
            $prices = [];
            $visibilityCustomer = [];

            foreach ($this->toArray($fieldsValues) as $fieldName => $values) {
                foreach ($this->toArray($values) as $value) {
                    $singleValueFieldName = $this->cleanFieldName($fieldName);
                    $value = $value['value'];
                    $placeholders = [];

                    if ($value instanceof PlaceholderValue) {
                        $placeholders = $value->getPlaceholders();
                        $value = $value->getValue();
                    }

                    if (\array_key_exists(LocalizationIdPlaceholder::NAME, $placeholders)) {
                        if ($localization->getId() != $placeholders[LocalizationIdPlaceholder::NAME]) {
                            continue;
                        }
                    }

                    if (str_starts_with($fieldName, 'assigned_to.')) {
                        $nodeId = $placeholders[AssignIdPlaceholder::NAME];
                        $categories[$nodeId]['id'] = $nodeId;
                        $nodeIds[] = $placeholders[AssignIdPlaceholder::NAME];
                    } elseif (str_starts_with($fieldName, 'assigned_to_sort_order.')) {
                        $nodeId = $placeholders[AssignIdPlaceholder::NAME];
                        $categories[$nodeId]['id'] = $nodeId;
                        $nodeIds[] = $placeholders[AssignIdPlaceholder::NAME];
                    } elseif (str_starts_with($fieldName, 'category_path')) {
                        // Todo
                    } elseif (str_starts_with($fieldName, 'manually_added_to')) {
                        // Todo
                    } elseif (str_starts_with($fieldName, 'ordered_at_by')) {
                        // Todo
                    } elseif (str_starts_with($fieldName, 'minimal_price.')) {
                        if (
                            str_contains($fieldName, UnitPlaceholder::NAME)
                            || $defaultCurrency !== $placeholders[CurrencyPlaceholder::NAME]
                        ) {
                            continue;
                        }
                        $groupId = $placeholders[CPLIdPlaceholder::NAME] ?: $placeholders[PriceListIdPlaceholder::NAME];
                        $groupId = $defaultPriceList->getId() === $groupId
                            ? 0
                            : (($placeholders[CPLIdPlaceholder::NAME] ? 'cpl_' : 'pl_') . $groupId);
                        $prices[] = ['price' => (float) $value, 'group_id' => $groupId];
                    } elseif (str_starts_with($fieldName, 'visibility_customer.')) {
                        $visibilityCustomer[] = [
                            'customer_id' => $placeholders[CustomerIdPlaceholder::NAME],
                            'value' => $value,
                        ];
                    } elseif (preg_match('/^(\w+)_enum\.(.+)$/', $fieldName, $matches)) {
                        [$fullMatch, $fieldName, $value] = $matches;
                        $preparedIndexData[$entityId][$fieldName][] = [
                            'label' => $optionValues[$fieldName][$value] ?? $value,
                            'value' => $value,
                        ];
                        $blop = 'toto';
                    // manage value / label
                    } elseif (!str_starts_with($fieldName, self::ALL_TEXT_PREFIX)) {
                        if (null === $value || '' === $value || [] === $value) {
                            continue;
                        }
                        $singleValueFieldName = $this->placeholder->replace($singleValueFieldName, $placeholders);
                        $preparedIndexData[$entityId][$singleValueFieldName] = $value;
                    }
                }
            }

            $preparedIndexData[$entityId] = $preparedIndexData[$entityId] ?? [];

            $preparedIndexData[$entityId]['id'] = $entityId;
            if (\array_key_exists('image_product_medium', $preparedIndexData[$entityId])) {
                $preparedIndexData[$entityId]['image'] = $preparedIndexData[$entityId]['image_product_medium'];
            }

            if (!empty($categories)) {
                $preparedIndexData[$entityId]['category'] = array_values($categories);
            }

            if (Product::class == $entityClass) {
                $preparedIndexData[$entityId]['price'] = $prices;
                $stockStatus = $preparedIndexData[$entityId]['inv_status'] ?? Product::INVENTORY_STATUS_OUT_OF_STOCK;
                $preparedIndexData[$entityId]['stock'] = [
                    'status' => Product::INVENTORY_STATUS_IN_STOCK == $stockStatus,
                    'qty' => $preparedIndexData[$entityId]['inv_qty'] ?? 0,
                ];
                unset($preparedIndexData[$entityId]['inv_status']);
                unset($preparedIndexData[$entityId]['inv_qty']);
            }

            if (!empty($visibilityCustomer)) {
                $preparedIndexData[$entityId]['visibility_customer'] = $visibilityCustomer;
            }
        }

        if (!empty($nodeIds)) {
            $this->addCategoryNames($preparedIndexData, $nodeIds, $localization);
        }

        return $preparedIndexData;
    }

    private function cleanFieldName(string $fieldName): string
    {
        $fieldName = trim(
            $this->placeholder->replace($fieldName, [LocalizationIdPlaceholder::NAME => null]),
            '_.'
        );

        return $this->attributeCodeMapping[$fieldName] ?? $fieldName;
    }

    private function addCategoryNames(array &$preparedIndexData, array $nodeIds, Localization $localization): void
    {
        $contentVariantNodeRepository = $this->doctrineHelper->getEntityRepositoryForClass(ContentVariant::class);
        $nodeNames = [];

        /** @var ContentVariant $node */
        foreach ($contentVariantNodeRepository->findBy(['id' => $nodeIds]) as $node) {
            $name = $this->localizationHelper->getLocalizedValue($node->getNode()->getTitles(), $localization)->getString();
            $nodeNames[$node->getId()] = [
                'id' => 'node_' . $node->getNode()->getId(),
                'name' => $name,
            ];
        }

        foreach ($preparedIndexData as $entityId => $entityData) {
            $newCategoryData = [];
            foreach ($entityData['category'] ?? [] as $index => $categoryData) {
                if (\array_key_exists($categoryData['id'], $nodeNames)) {
                    $newCategoryData[] = $nodeNames[$categoryData['id']];
                }
            }
            unset($preparedIndexData[$entityId]['category']);
            $preparedIndexData[$entityId]['category'] = $newCategoryData;
        }
    }

    private function prepareOptionValues(string $entityClass, array $indexData, Localization $localization): array
    {
        $entityConfig = $this->mappingProvider->getEntityConfig($entityClass);
        $selectAttributes = [];

        foreach ($entityConfig['fields'] as $fieldData) {
            $fieldName = $fieldData['name'];

            if (!str_ends_with($fieldName, '_enum.ENUM_ID')) {
                // Get options only for select attributes.
                continue;
            }

            $fieldName = preg_replace('/_enum\.ENUM_ID$/', '', $fieldName);
            $selectAttributes[] = $fieldName;
        }

        $usedOptionsByField = [];
        $selectAttributesMap = array_fill_keys($selectAttributes, true);
        foreach ($indexData as $entityData) {
            foreach ($entityData as $fieldName => $value) {
                if (preg_match('/^(\w+)_enum\.(.+)$/', $fieldName, $matches)) {
                    [$fullMatch, $attribute, $optionCode] = $matches;
                    if (isset($selectAttributesMap[$attribute])) {
                        $usedOptionsByField[$attribute][$optionCode] = $optionCode;
                    }
                }
            }
        }

        $translatedOptionsByField = [];
        foreach ($usedOptionsByField as $fieldName => $optionCodes) {
            $enumCode = $this->enumTypeHelper->getEnumCode($entityClass, $fieldName);
            $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
            $translationRepo = $this->entityManager->getRepository(EnumValueTranslation::class);
            $translations = $translationRepo->findBy([
                'objectClass' => $enumValueClassName,
                'field' => 'name',
                'foreignKey' => $optionCodes,
                'locale' => $localization->getFormattingCode(),
            ]);
            foreach ($translations as $translation) {
                $translatedOptionsByField[$fieldName][$translation->getForeignKey()] = $translation->getContent();
            }
        }

        return $translatedOptionsByField;
    }

    /**
     * @return array
     */
    private function toArray($value)
    {
        if (\is_array($value) && !\array_key_exists('value', $value)) {
            return $value;
        }

        return [$value];
    }

    private function getDefaultPriceListForWebsite(Website $website): PriceList|CombinedPriceList|null
    {
        $isCombinedPriceListEnable = $this->featureChecker->isFeatureEnabled('oro_price_lists_combined');

        if ($isCombinedPriceListEnable) {
            /** @var CombinedPriceListRepository $combinedPriceListRepository */
            $combinedPriceListRepository = $this->doctrineHelper->getEntityRepositoryForClass(CombinedPriceList::class);

            return $combinedPriceListRepository->getPriceListByWebsite($website, true);
        }
        $priceListId = $this->configManager->get('oro_pricing.default_price_list', false, false, $website->getId());
        if ($priceListId) {
            return $this->doctrineHelper->getEntityRepositoryForClass(PriceList::class)->find($priceListId);
        }

        return null;
    }
}
