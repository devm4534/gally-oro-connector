<?php

namespace Gally\OroPlugin\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderDecorator as BasePlaceholderDecorator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

/**
 * Replaces placeholders at with an appropriate values using all registered placeholders
 * In gally context, placeholder are removed from field names and manage in the data directly.
 */
class PlaceholderDecorator extends BasePlaceholderDecorator implements PlaceholderInterface
{
    public function replaceDefault($string)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $string = $placeholder->replace($string, [$placeholder->getPlaceholder() => null]);
        }

        return trim($string, '._-');
    }
}
