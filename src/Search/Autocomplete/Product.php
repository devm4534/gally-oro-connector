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

namespace Gally\OroPlugin\Search\Autocomplete;

use Gally\OroPlugin\Service\ContextProvider;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;

/**
 * Save autocomplete context for product query building.
 */
class Product
{
    public function __construct(
        private ContextProvider $contextProvider,
    ) {
    }

    public function onProcessAutocompleteQuery(ProcessAutocompleteQueryEvent $event): void
    {
        $this->contextProvider->setIsAutocompleteContext(true);
    }
}
