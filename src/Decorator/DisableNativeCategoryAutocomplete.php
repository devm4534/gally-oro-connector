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

namespace Gally\OroPlugin\Decorator;

use Gally\OroPlugin\Search\SearchEngine;
use Oro\Bundle\CatalogBundle\EventListener\Search\AddCategoryToProductAutocompleteListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DisableNativeCategoryAutocomplete extends AddCategoryToProductAutocompleteListener
{
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        HtmlTagExtension $htmlTagExtension,
        ConfigManager $configManager,
        private AddCategoryToProductAutocompleteListener $decorated,
        private EngineParameters $engineParameters,
    ) {
        parent::__construct($urlGenerator, $htmlTagExtension, $configManager);
    }

    public function onProcessAutocompleteQuery(ProcessAutocompleteQueryEvent $event): void
    {
        if (SearchEngine::ENGINE_NAME === $this->engineParameters->getEngineName()) {
            return;
        }

        $this->decorated->onProcessAutocompleteQuery($event);
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event): void
    {
        if (SearchEngine::ENGINE_NAME === $this->engineParameters->getEngineName()) {
            return;
        }

        $this->decorated->onProcessAutocompleteData($event);
    }
}
