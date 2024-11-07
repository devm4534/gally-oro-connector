<?php

namespace Oro\Bundle\WebsiteElasticSearchBundle\Voter;

use Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
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
        if ($feature !== self::ELASTIC_SEARCH_ENGINE_FEATURE_KEY) {
            return self::FEATURE_ABSTAIN;
        }

        if ($this->engineParametersBag->getEngineName() === ElasticSearch::ENGINE_NAME) {
            return self::FEATURE_ENABLED;
        }

        return self::FEATURE_DISABLED;
    }
}
