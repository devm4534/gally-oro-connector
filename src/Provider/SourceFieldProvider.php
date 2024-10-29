<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Provider;

use Gally\Sdk\Entity\Label;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteElasticSearchBundle\Entity\SavedSearch;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gally Catalog data provider.
 */
class SourceFieldProvider
{
    // Get this from conf todo
    private array $entityCodeMapping = [
        'contentnode' => 'category',
    ];

    private array $typeMapping = [
        Query::TYPE_TEXT => SourceField::TYPE_TEXT,
        Query::TYPE_DECIMAL => SourceField::TYPE_FLOAT,
        Query::TYPE_INTEGER => SourceField::TYPE_INT,
        Query::TYPE_DATETIME => SourceField::TYPE_DATE,
    ];

    /** @var LocalizedCatalog[] */
    private array $localizedCatalogs = [];

    public function __construct(
        private SearchMappingProvider $mappingProvider,
        private EntityAliasResolver $entityAliasResolver,
        private ConfigProvider $configProvider,
        private CatalogProvider $catalogProvider,
        private PlaceholderRegistry $placeholderRegistry,
        private TranslatorInterface $translator,
        private LocaleSettings $localeSettings,
    ) {
        foreach ($this->catalogProvider->provide() as $localizedCatalog) {
            $this->localizedCatalogs[] = $localizedCatalog;
        }
    }

    /**
     * @return iterable<SourceField>
     * @see \Oro\Bundle\ProductBundle\EventListener\WebsiteSearchMappingListener:54
     */
    public function provide(): iterable
    {
        foreach ($this->mappingProvider->getEntityClasses() as $entityClass) {

            if ($entityClass === SavedSearch::class) {
                // Todo managed savedSearch https://doc.oroinc.com/user/storefront/account/saved-search/
                continue;
            }

            $metadata = $this->getMetadataFromEntityClass($entityClass);
            $entityConfig = $this->mappingProvider->getEntityConfig($entityClass);

            foreach ($entityConfig['fields'] as $fieldData) {
                $fieldName = $this->cleanFieldName($fieldData['name']);
                if ($fieldName === 'visibility_customer') {
                    // Field managed manually
                    // @see src/Resources/config/oro/website_search.yml
                    continue;
                }
                $fieldType = $this->typeMapping[$fieldData['type']] ?? SourceField::TYPE_TEXT;

                if (str_ends_with($fieldName, '_enum')) {
                    $fieldName = preg_replace('/_enum$/', '', $fieldName);
                    $fieldType = SourceField::TYPE_SELECT;
                }

                try {
                    $fieldConfig = $this->configProvider->getConfig($entityClass, $fieldName);
                    $labelKey = $fieldConfig->get('label');
                } catch (RuntimeException) {
                    $labelKey = $fieldName;
                }
                $defaultLabel = $this->translator->trans($labelKey, [], null,  $this->getDefaultLocale());

                if (!array_key_exists($fieldData['type'], $this->typeMapping)) {
                    throw new \LogicException(
                        sprintf(
                            'Type %s not managed for field %s of entity %s.',
                            $fieldData['type'],
                            $fieldName,
                            $entityClass
                        )
                    );
                }

                yield new SourceField(
                    $metadata,
                    $fieldName,
                    $fieldType,
                    $defaultLabel,
                    $this->getLabels($labelKey, $defaultLabel),
                );
            }
        }
    }

    public function getMetadataFromEntityClass(string $entityClass): Metadata
    {
        $entityCode = $this->entityAliasResolver->getAlias($entityClass);
        return new Metadata($this->entityCodeMapping[$entityCode] ?? $entityCode);
    }

    public function cleanFieldName(string $fieldName): string
    {
        // Todo customer placeHolder helper here !
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $fieldName = $placeholder->replace($fieldName, [$placeholder->getPlaceholder() => null]);
        }

        return trim($fieldName, '._-');
    }

    private function getDefaultLocale(): string
    {
        return $this->localeSettings->getLocaleWithRegion();
    }

    /**
     * @return Label[]
     */
    private function getLabels(string $labelKey, string $defaultLabel): array
    {
        $defaultLocale = $this->getDefaultLocale();
        $labels = [];
        foreach ($this->localizedCatalogs as $localizedCatalog) {
            if ($localizedCatalog->getLocale() != $defaultLocale) {
                $label = $this->translator->trans($labelKey, [], null, $localizedCatalog->getLocale());
                if ($label !== $defaultLabel) {
                    $labels[] = new Label($localizedCatalog, $label);
                }
            }
        }

        return $labels;
    }
}
