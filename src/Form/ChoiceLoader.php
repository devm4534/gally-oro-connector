<?php

namespace Gally\OroPlugin\Form;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Dummy choice loader class for gally filter.
 * With gally, we cannot validate facet option before running the search request.
 * This class disabled these check and let gally manage wrong value.
 */
class ChoiceLoader implements ChoiceLoaderInterface
{
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList([]);
    }

    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        return $values;
    }

    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return $choices;
    }
}
