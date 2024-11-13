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

namespace Gally\OroPlugin\Voter;

use Gally\OroPlugin\Search\SearchEngine;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;

/**
 * Elastic search feature availability voter.
 */
class ElasticSearchEngineFeatureVoter implements VoterInterface
{
    private const ELASTIC_SEARCH_ENGINE_FEATURE_KEY = 'elastic_search_engine';

    private EngineParameters $engineParametersBag;

    public function __construct(EngineParameters $engineParametersBag)
    {
        $this->engineParametersBag = $engineParametersBag;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ('elastic_search_engine' === $feature
            || 'recommendation_action_boost' === $feature
            || 'total_revenue_boost' === $feature) {
            if (SearchEngine::ENGINE_NAME === $this->engineParametersBag->getEngineName()
                || 'elastic_search' === $this->engineParametersBag->getEngineName()
            ) {
                return self::FEATURE_ENABLED;
            }

            return self::FEATURE_DISABLED;
        }

        if ('saved_search' === $feature) { // Todo
            return self::FEATURE_DISABLED;
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
