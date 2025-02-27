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

namespace Gally\OroPlugin\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to reindex the specified entities by class and ids within a job.
 */
class WebsiteSearchReindexGranulizedJobAwareTopic extends AbstractTopic
{
    public const NAME = 'oro.website.search.indexer.reindex_granulized.process_job';
    public const JOB_ID = 'jobId';

    private WebsiteSearchReindexGranulizedTopic $innerTopic;

    public function __construct(WebsiteSearchReindexGranulizedTopic $innerTopic)
    {
        $this->innerTopic = $innerTopic;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Reindex the specified entities by class and ids within a job.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->innerTopic->configureMessageBody($resolver);

        $resolver
            ->setDefined(self::JOB_ID)
            ->setAllowedTypes(self::JOB_ID, 'int');
    }
}
