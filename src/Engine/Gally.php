<?php

namespace Gally\OroPlugin\Engine;

use Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;

/**
 * Class Gally
 *
 * @author Pierre Gauthier <pigau@smile.fr>
 * @copyright 2019
 *
 *  Todo remove es dependencies
 */
class Gally extends ElasticSearch
{
    public const ENGINE_NAME = 'gally';
}
