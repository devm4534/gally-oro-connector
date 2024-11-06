<?php

namespace Gally\OroPlugin\Extension;

use Gally\OroPlugin\Registry\SearchRegistry;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\GraphQl\Response;
use Gally\Sdk\Service\SearchManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * Adapt data grid for result managed by Gally.
 */
class GallyDataAfterGridExtension extends AbstractExtension
{
    public function __construct(
        private SearchManager $searchManager,
        private SearchRegistry $registry,
    ) {
    }

    public function isApplicable(DatagridConfiguration $config): bool
    {
        // Todo it is gally search engine
        return $config->getName() === 'frontend-product-search-grid';
    }

    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $toto = 'blop';
//        $this->addFilterFieldsFromGallyConfiguration($config);
//        $this->addSortFieldsFromGallyConfiguration($config);
    }

    public function visitMetadata(DatagridConfiguration $config, MetadataObject $object)
    {
//        $this->addFiltersFromGallyResult($object);
    }

    public function getPriority()
    {
        return -255;
    }

    private function addSortFieldsFromGallyConfiguration(DatagridConfiguration $config)
    {
        $sortableAttributes = $this->searchManager->getProductSortingOptions();

        /** @var SourceField[] $sorters */
        $sorters = [];
        $config->offsetSetByPath('[sorters][columns]', []);
        foreach ($sortableAttributes as $attribute) {
            $sorters[$attribute->getCode()] = $attribute;
            $config->offsetSetByPath(
                '[sorters][columns][' . $attribute->getCode() . ']',
                ['data_name' => $attribute->getCode()]
            );
        }

        $proceed = [];
        foreach ($config->offsetGetOr('columns', []) as $name => $column) {
            if (array_key_exists($name, $sorters)) {
                $proceed[] = $name;
            }
        }

        $columnToAdd = array_diff(array_keys($sorters), $proceed);
        foreach($columnToAdd as $attributeCode) {
            $attribute = $sorters[$attributeCode];
            $columnData = [
                'label' => $attribute->getDefaultLabel(),
                'type' => 'field',
                'frontend_type' => 'string',
                'translatable' => false,
                'editable' => false,
                'shortenableLabel' => true,
            ];
            $config->offsetAddToArrayByPath('[columns]', [$attribute->getCode() => $columnData]);
        }
    }

    private function addFilterFieldsFromGallyConfiguration(DatagridConfiguration $config)
    {
        $filterableSourceField = $this->searchManager->getFilterableSourceField(new Metadata('product')); // todo get entity from  context & cache this !
        $filters = $config->offsetGetByPath('[filters][columns]');

        $proceed = [];
        foreach ($filters as $filter) {
            $name = $filter['data_name'];
            $proceed[] = $name;
        }

        foreach ($filterableSourceField as $sourceField) {
            if (!in_array($sourceField->getCode(), $proceed)) {
                $filter = [
                    'name' => $sourceField->getCode(),
                    'label' => $sourceField->getDefaultLabel(),
                    'visible' => false,
                    'disabled' => false,
                    'renderable' => true,
                    'choices' => [],
                ];

                // @see \Gally\Search\Decoration\GraphQl\AddAggregationsData::formatAggregation
                $filter['type'] = match ($sourceField?->getType()) {
                    SourceField::TYPE_PRICE => 'frontend-product-price',
//                SourceField::TYPE_FLOAT, SourceField::TYPE_INT => self::AGGREGATION_TYPE_SLIDER,
//                SourceField::TYPE_CATEGORY => self::AGGREGATION_TYPE_CATEGORY,
//                SourceField::TYPE_STOCK, SourceField::TYPE_BOOLEAN => self::AGGREGATION_TYPE_BOOLEAN,
//                SourceField::TYPE_DATE => self::AGGREGATION_TYPE_DATE_HISTOGRAM,
//                SourceField::TYPE_LOCATION => self::AGGREGATION_TYPE_HISTOGRAM,
                    default => 'multichoice',
                };

                $filters[] = $filter;
            }
        }

        $config->offsetSetByPath('[filters][columns]', $filters);
    }

    private function addFiltersFromGallyResult(MetadataObject $object)
    {
        $gallyFilters = $this->registry->getResponse()->getAggregations();
        $currentFilters = $object->offsetGetByPath('[filters]');
        $filters = [];

        // current : all_text, sku, names, brand, minimal_price,

        foreach ($currentFilters as $filter) {
            $name = $filter['name'];
            // Todo check if we should keep these default filters
            if (in_array($name, ['all_text', 'sku', 'names'])) {
                $filters[] = $filter;
            }
        }

        foreach ($gallyFilters as $gallyFilter) {
            $filter = [
                'name' => $gallyFilter['field'],
                'label' => $gallyFilter['label'],
                'visible' => true,
                'disabled' => false,
                'renderable' => true,
            ];
            if ($gallyFilter['type'] === Response::FILTER_TYPE_CHECKBOX) {
                $filter['choices'] = $gallyFilter['options'];
                $filter['type'] = 'multichoice'; //  todo gally-select ??
                $filters[] = $filter;
//                    "field_options": {
//                                    "type": "ref-one"
//                    },
//                    "type": "multichoice",
//                    "force_like": true,
//                    "translatable": true,
//                    "case_insensitive": true,
//                    "min_length": 0,
//                    "max_length": 9223372036854775807,
//                    "order": 4,
//                    "lazy": false,
//                    "populateDefault": false,
//                    "cacheId": null
            } elseif ($gallyFilter['type'] === Response::FILTER_TYPE_SLIDER) {
                $filter['type'] = 'frontend-product-price';
                $filters[] = $filter;
//        "choices": [
//            {
//                "label": "between",
//                "value": "7",
//                "data": 7,
//                "attr": [],
//                "labelTranslationParameters": []
//            },
//            {
//                "label": "equals",
//                "value": "3",
//                "data": 3,
//                "attr": [],
//                "labelTranslationParameters": []
//            },
//            {
//                "label": "more than",
//                "value": "2",
//                "data": 2,
//                "attr": [],
//                "labelTranslationParameters": []
//            },
//            {
//                "label": "less than",
//                "value": "6",
//                "data": 6,
//                "attr": [],
//                "labelTranslationParameters": []
//            },
//            {
//                "label": "equals or more than",
//                "value": "1",
//                "data": 1,
//                "attr": [],
//                "labelTranslationParameters": []
//            },
//            {
//                "label": "equals or less than",
//                "value": "5",
//                "data": 5,
//                "attr": [],
//                "labelTranslationParameters": []
//            }
//        ],
//        "type": "frontend-product-price",
//        "translatable": true,
//        "force_like": false,
//        "case_insensitive": true,
//        "min_length": 0,
//        "max_length": 9223372036854775807,
//        "order": 5,
//        "lazy": false,
//        "formatterOptions": {
//                    "grouping": true,
//            "orderSeparator": ",",
//            "decimalSeparator": "."
//        },
//        "arraySeparator": ",",
//        "arrayOperators": [
//                    9,
//                    10
//                ],
//        "dataType": "data_decimal",
//        "unitChoices": [
//            {
//                "data": "each",
//                "value": "each",
//                "label": "each",
//                "shortLabel": "ea"
//            },
//            {
//                "data": "hour",
//                "value": "hour",
//                "label": "hour",
//                "shortLabel": "hr"
//            },
//            {
//                "data": "item",
//                "value": "item",
//                "label": "item",
//                "shortLabel": "item"
//            },
//            {
//                "data": "kg",
//                "value": "kg",
//                "label": "kilogram",
//                "shortLabel": "kg"
//            },
//            {
//                "data": "piece",
//                "value": "piece",
//                "label": "piece",
//                "shortLabel": "pc"
//            },
//            {
//                "data": "set",
//                "value": "set",
//                "label": "set",
//                "shortLabel": "set"
//            }
//        ],
//        "cacheId": null
            } else {
                // Todo boolean
                $toto = 'blop';
            }
        }

//        $choices = $object->offsetGetByPath('[filters][0][choices]');
//        $object->offsetAddToArrayByPath(
//            '[filters]',
//            [
//                [
//                    'name' => 'test_color',
//                    'label' => 'Cumtom filter',
//                    'choices' => $choices,
//                    'type' => 'string',
//                    'max_length' => 255,
//                    'translatable' => true,
//                    'force_like' => false,
//                    'case_insensitive' => true,
//                    'min_length' => 0,
//                    'order' => 1,
//                    'lazy' => false,
//                    'cacheId' => null,
//                ]
//            ]
//        );

        $object->offsetSetByPath('[filters]', $filters);
    }
}
