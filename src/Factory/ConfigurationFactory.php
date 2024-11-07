<?php

namespace Gally\OroPlugin\Factory;

use Gally\Sdk\Client\Configuration;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;

class ConfigurationFactory
{
    public static function create(EngineParameters $engineParameters): Configuration
    {
        $scheme = $engineParameters->getPort() === '443' ? 'https' : 'http';
        $url = "$scheme://{$engineParameters->getHost()}:{$engineParameters->getPort()}";
        return new Configuration(
            $url,
            stripslashes($engineParameters->getUser()),
            stripslashes($engineParameters->getPassword()),
        );
    }
}
