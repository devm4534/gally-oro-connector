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

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GallyReindexFinishedTopic extends AbstractTopic
{
    public const NAME = 'gally:reindex:finished';
    public const ROOT_JOB_ID = 'root_job_id';
    public const INDICIES_BY_LOCALE = 'indices_by_locale';
    public const IS_FULL_REINDEX = 'is_full_reindex';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Install index after the reindex of all messages queued.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(self::ROOT_JOB_ID)
            ->setAllowedTypes(self::ROOT_JOB_ID, 'int')
            ->setDefined(self::INDICIES_BY_LOCALE)
            ->setAllowedTypes(self::INDICIES_BY_LOCALE, ['array'])
            ->setDefined(self::IS_FULL_REINDEX)
            ->setAllowedTypes(self::IS_FULL_REINDEX, 'bool');
    }

    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::VERY_HIGH;
    }
}
