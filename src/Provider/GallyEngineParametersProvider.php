<?php

namespace Gally\OroPlugin\Provider;

use Gally\OroPlugin\Engine\Gally;
use Oro\Bundle\ElasticSearchBundle\Provider\ElasticSearchEngineParametersProvider;

/**
 * Class GallyEngineParametersProvider
 *
 * Todo remove es dependencies
 *
 * @author Pierre Gauthier <pigau@smile.fr>
 * @copyright 2019
 */
class GallyEngineParametersProvider extends ElasticSearchEngineParametersProvider
{
    public function isConfigured(): bool
    {
        return $this->engineParametersBag->getEngineName() === Gally::ENGINE_NAME;
    }
}
