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
