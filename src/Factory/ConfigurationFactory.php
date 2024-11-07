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

namespace Gally\OroPlugin\Factory;

use Gally\Sdk\Client\Configuration;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;

class ConfigurationFactory
{
    public static function create(EngineParameters $engineParameters): Configuration
    {
        $scheme = '443' === $engineParameters->getPort() ? 'https' : 'http';
        $url = "$scheme://{$engineParameters->getHost()}:{$engineParameters->getPort()}";

        return new Configuration(
            $url,
            stripslashes($engineParameters->getUser()),
            stripslashes($engineParameters->getPassword()),
        );
    }
}
