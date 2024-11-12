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

namespace Gally\OroPlugin\Search\Filter\Form;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The filter by a multi-enum entity for a datasource based on a search index.
 */
class SelectFormFilter extends AbstractType
{
    public const NAME = 'gally_search_type_select_filter';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('gally_options', []);
        $resolver->setNormalizer(
            'gally_options',
            function (Options $options, $value) {
                return !empty($value) ? array_column($value, 'label', 'value') : [];
            }
        );

        //        $resolver->setNormalizer( //todo might be removed
        //            'class',
        //            function (Options $options, $value) {
        //                    return null;
        //            }
        //        );

        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                $value['choices'] = $options['gally_options'];
                $value['choice_loader'] = new ChoiceLoader();
                $value['choice_value'] = function ($value = null, $data = null, $toto = null) {
                    return $value;
                };
                $value['choice_filter'] = function ($value = null, $data = null, $toto = null) {
                    return $value;
                };

                if (!isset($value['translatable_options'])) {
                    $value['translatable_options'] = false;
                }
                if (!isset($value['multiple'])) {
                    $value['multiple'] = true;
                }

                return $value;
            }
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $toto = 'blop';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return ChoiceFilterType::class;
    }
}
