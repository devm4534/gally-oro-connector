<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Provider;

use Gally\Sdk\Entity\Label;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gally Catalog data provider.
 */
class SourceFieldProvider
{
    // Get this from conf todo
    private array $entities = [
        Category::class => 'category',
        Brand::class    => 'brand',
        Product::class  => 'product',
    ];

    private array $typeMapping = [
        Type\BooleanSearchableAttributeType::class      => SourceField::TYPE_BOOLEAN,
        Type\DateSearchableAttributeType::class         => SourceField::TYPE_DATE,
        Type\DecimalSearchableAttributeType::class      => SourceField::TYPE_FLOAT,
        Type\EnumSearchableAttributeType::class         => SourceField::TYPE_SELECT,
        Type\FileSearchableAttributeType::class         => SourceField::TYPE_TEXT,
        Type\IntegerSearchableAttributeType::class      => SourceField::TYPE_INT,
        Type\ManyToManySearchableAttributeType::class   => SourceField::TYPE_SELECT,
        Type\ManyToOneSearchableAttributeType::class    => SourceField::TYPE_SELECT,
        Type\MultiEnumSearchableAttributeType::class    => SourceField::TYPE_SELECT,
        Type\OneToManySearchableAttributeType::class    => SourceField::TYPE_SELECT,
        Type\PercentSearchableAttributeType::class      => SourceField::TYPE_FLOAT,
        Type\StringSearchableAttributeType::class       => SourceField::TYPE_TEXT,
        Type\TextSearchableAttributeType::class         => SourceField::TYPE_TEXT,
        Type\WYSIWYGSearchableAttributeType::class      => SourceField::TYPE_TEXT,
    ];


    /** @var LocalizedCatalog[] */
    private array $localizedCatalogs = [];

    public function __construct(
        private AttributeManager $attributeManager,
        private ConfigProvider $configProvider,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private CatalogProvider $catalogProvider,
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
        // Todo get entity list as in indexation
        foreach (array_keys($this->entities) as $entityClass) {

            // Todo se baser sur le mapping généré pour avoir les champs action er revenue
            $metadata = $this->getMetadataFromEntityClass($entityClass);
            $attributes = $this->attributeManager->getAttributesByClass($entityClass);
            foreach ($attributes as $attribute) {
                $fieldConfig = $this->configProvider->getConfig($entityClass, $attribute->getFieldName());
                $labelKey = $fieldConfig->get('label');
                $defaultLabel = $this->translator->trans($labelKey, [], null,  $this->getDefaultLocale());

                yield new SourceField(
                    $metadata,
                    $attribute->getFieldName(),
                    $this->getGallyType($attribute),
                    $defaultLabel,
                    $this->getLabels($labelKey, $defaultLabel),
                );
            }
        }

        return [];
    }

    public function getMetadataFromEntityClass(string $entityClass): Metadata
    {
        return new Metadata($this->entities[$entityClass]); // Todo manage undefined
    }

    private function getDefaultLocale(): string
    {
        return $this->localeSettings->getLocaleWithRegion();
    }

    private function getGallyType(FieldConfigModel $attribute): string
    {
        $oroType = $this->attributeTypeRegistry->getAttributeType($attribute);
        return $oroType ? ($this->typeMapping[get_class($oroType)] ?? SourceField::TYPE_TEXT) : SourceField::TYPE_TEXT;
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
