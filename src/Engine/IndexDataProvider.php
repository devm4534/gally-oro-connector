<?php

namespace Gally\OroPlugin\Engine;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider as BaseIndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class is responsible for triggering all events during indexation
 * and returning all collected and prepared for saving event data
 */
class IndexDataProvider extends BaseIndexDataProvider
{
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
        private PlaceholderHelper $placeholderHelper,
        private DoctrineHelper $doctrineHelper,
        private LocalizationHelper $localizationHelper,
    ) {
        parent::__construct($eventDispatcher, $entityAliasResolver, $placeholder, $htmlTagHelper, $placeholderHelper);
    }

    /**
     * @param string $entityClass
     * @param object[] $restrictedEntities
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @param array $entityConfig
     * @return array
     */
    public function getEntitiesData($entityClass, array $restrictedEntities, array $context, array $entityConfig)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $indexEntityEvent = new Event\IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch($indexEntityEvent, Event\IndexEntityEvent::NAME);
        $this->eventDispatcher->dispatch(
            $indexEntityEvent,
            sprintf('%s.%s', Event\IndexEntityEvent::NAME, $entityAlias)
        );

        return $this->prepareIndexData($indexEntityEvent->getEntitiesData(), $entityConfig, $context);
    }

    /**
     * Adds field types according to entity config, applies placeholders
     * @param array $indexData
     * @param array $entityConfig
     * @param array $context
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(array $indexData, array $entityConfig, array $context): array
    {
        $preparedIndexData = [];
        $nodeIds = [];

        /** @var Localization $localization */
        $localization = $context[GallyIndexer::CONTEXT_LOCALIZATION];

        foreach ($indexData as $entityId => $fieldsValues) {

            $categories = [];

            foreach ($this->toArray($fieldsValues) as $fieldName => $values) {
                $type = $this->getFieldConfig($entityConfig, $fieldName, 'type');

                foreach ($this->toArray($values) as $value) {
                    $singleValueFieldName = $this->cleanFieldName($fieldName);
                    $value = $value['value'];
                    $placeholders = [];

                    if ($value instanceof PlaceholderValue) {
                        $placeholders = $value->getPlaceholders();
                        $value = $value->getValue();
                    }

                    if (array_key_exists(LocalizationIdPlaceholder::NAME, $placeholders)) {
                        if ($localization->getId() != $placeholders[LocalizationIdPlaceholder::NAME]) {
                            continue;
                        }
                    }

                    if (str_starts_with($fieldName, 'assigned_to.')) {
                        $nodeId = 'node_' . $placeholders[AssignIdPlaceholder::NAME];
                        $categories[$nodeId]['id'] = $nodeId;
                        $nodeIds[] = $placeholders[AssignIdPlaceholder::NAME];
                        continue;
                    } elseif (str_starts_with($fieldName, 'assigned_to_sort_order.')) {
                        $nodeId = 'node_' . $placeholders[AssignIdPlaceholder::NAME];
                        $categories[$nodeId]['position'] = (int) $value;
                        continue;
                    }

                    if (!str_starts_with($fieldName, self::ALL_TEXT_PREFIX)) {
                        $singleValueFieldName = $this->placeholder->replace($singleValueFieldName, $placeholders);
                        $this->setIndexValue($preparedIndexData, $entityId, $singleValueFieldName, $value, $type);
                    }
                }
            }

            $preparedIndexData[$entityId] = $preparedIndexData[$entityId] ?? [];

            // Spe gally
            $preparedIndexData[$entityId]['id'] = $entityId;
            if (array_key_exists('image_product_small', $preparedIndexData[$entityId])) {
                $preparedIndexData[$entityId]['image'] = $preparedIndexData[$entityId]['image_product_small'];
            }

            // Todo provisoir : only for product entity ??
            if (!array_key_exists('name', $preparedIndexData[$entityId])) {
                $preparedIndexData[$entityId]['name'] = 'Blop #' . $entityId;
            }
            $preparedIndexData[$entityId]['price'] = ['price' => 0, 'group_id' => 0];
            $preparedIndexData[$entityId]['stock'] = ['status' => true, 'qty' => 0];

            if (!empty($categories)) {
                $preparedIndexData[$entityId]['category'] = array_values($categories);
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
        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass(ContentNode::class);
        $nodeNames = [];

        /** @var ContentNode $node */
        foreach ($entityRepository->findBy(['id' => $nodeIds]) as $node) {
            $name = $this->localizationHelper->getLocalizedValue($node->getTitles(), $localization)->getString();
            $nodeNames['node_' . $node->getId()] = $name;
        }

        foreach ($preparedIndexData as $entityId => $entityData) {
            foreach ($entityData['category'] ?? [] as $index => $categoryData) {
                if (array_key_exists($categoryData['id'], $nodeNames)) {
                    $preparedIndexData[$entityId]['category'][$index]['name'] = $nodeNames[$categoryData['id']];
                }
            }
        }
        // Todo manage undefined : certain ne semble pas être des contentNode : le 40 ?
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function toArray($value)
    {
        if (is_array($value) && !array_key_exists('value', $value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fieldName
     * @param array|string $value
     * @param string $type
     */
    private function setIndexValue(array &$preparedIndexData, $entityId, $fieldName, $value, $type = Query::TYPE_TEXT)
    {
        $value = $this->clearValue($type, $fieldName, $value);

        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $existingValue = $this->getIndexValue($preparedIndexData, $entityId, $fieldName, $type);
        if ($existingValue) {
            $value = $this->updateFieldValue($existingValue, $value, $type);
        }

        $preparedIndexData[$entityId][$fieldName] = $value;
    }

    /**
     * @param string|array  $existingValue
     * @param string        $value
     *
     * @return string|array
     */
    private function updateFieldValue($existingValue, $value, $type)
    {
        if ($type === Query::TYPE_TEXT && is_string($existingValue) && is_string($value)) {
            return $existingValue . ' ' . $value;
        }

        // array_values is required here to make sure that array can be properly converted to json
        return array_values(array_unique(array_merge((array)$existingValue, (array)$value)));
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fieldName
     * @param string $type
     * @return string|array
     */
    private function getIndexValue(array &$preparedIndexData, $entityId, $fieldName, $type = Query::TYPE_TEXT)
    {
        return $preparedIndexData[$entityId][$type][$fieldName] ?? '';
    }

    /**
     * @param array $entityConfig
     * @param string $fieldName
     * @param string $configName
     * @param string $default
     * @return string
     * @throws InvalidConfigurationException
     */
    private function getFieldConfig(array $entityConfig, $fieldName, $configName, $default = null)
    {
        $cacheKey = md5(json_encode($entityConfig)) . $fieldName . $configName;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fields = array_filter($entityConfig['fields'], function ($fieldConfig) use ($fieldName, $configName) {
            if (!array_key_exists('name', $fieldConfig)) {
                return false;
            }

            if (!array_key_exists($configName, $fieldConfig)) {
                return false;
            }

            return $fieldConfig['name'] === $fieldName ||
                $this->placeholderHelper->isNameMatch($fieldConfig['name'], $fieldName);
        });

        if (!$fields) {
            if ($default) {
                return $default;
            }

            if ($fieldName === self::ALL_TEXT_L10N_FIELD) {
                return $configName === 'type' ? Query::TYPE_TEXT : $fieldName;
            }

            throw new InvalidConfigurationException(
                sprintf('Missing option "%s" for "%s" field', $configName, $fieldName)
            );
        }

        $field = $this->findBestMatchedFieldConfig($fields);

        $result = $field[$configName];

        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Keep HTML in text fields except all_text* fields
     *
     * @param string $type
     * @param string $fieldName
     * @param mixed $value
     * @return mixed|string
     */
    protected function clearValue($type, $fieldName, $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $element) {
                $value[$key] = $this->clearValue($type, $fieldName, $element);
            }

            return $value;
        }

        return $value;
    }

    /**
     * @param string $entityClass
     * @param QueryBuilder $queryBuilder
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return QueryBuilder
     */
    public function getRestrictedEntitiesQueryBuilder($entityClass, $queryBuilder, array $context)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new Event\RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch($restrictEntitiesEvent, Event\RestrictIndexEntityEvent::NAME);
        $this->eventDispatcher->dispatch(
            $restrictEntitiesEvent,
            sprintf('%s.%s', Event\RestrictIndexEntityEvent::NAME, $entityAlias)
        );

        return $restrictEntitiesEvent->getQueryBuilder();
    }

    /**
     * Finds best matched field config based on length of field name without placeholders
     */
    private function findBestMatchedFieldConfig(array $fields): array
    {
        $field = end($fields);

        if (count($fields) > 1) {
            $availablePlaceholders = $this->placeholderHelper->getPlaceholderKeys();

            $lastCheckedFieldLength = 0;
            foreach ($fields as $keyFieldName => $config) {
                $cleanFieldName = str_replace($availablePlaceholders, '', $keyFieldName);
                if (strlen($cleanFieldName) > $lastCheckedFieldLength) {
                    $lastCheckedFieldLength = strlen($cleanFieldName);
                    $field = $config;
                }
            }
        }

        return $field;
    }
}
