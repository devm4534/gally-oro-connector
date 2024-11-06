<?php

namespace Gally\OroPlugin\Form;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The filter by a multi-enum entity for a datasource based on a search index.
 */
class SelectFormFilter extends AbstractType
{
    const NAME = 'gally_search_type_select_filter';

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
                $value['choice_loader'] = new \Gally\OroPlugin\Form\ChoiceLoader();
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
