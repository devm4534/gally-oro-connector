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

namespace Gally\OroPlugin\Async\Processor;

use Gally\OroPlugin\Async\Topic\GallyReindexFinishedTopic;
use Gally\OroPlugin\Indexer\Indexer;
use Monolog\Logger;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class InstallIndexProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected Indexer $indexer,
    ) {
    }

    /** {@inheritDoc} */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $messageData = JSON::decode($message->getBody());
            if ($messageData[GallyReindexFinishedTopic::IS_FULL_REINDEX]) {
                foreach ($messageData[GallyReindexFinishedTopic::INDICIES_BY_LOCALE] as $websiteId => $entities) {
                    foreach ($entities as $entity => $locales) {
                        foreach ($locales as $locale => $index) {
                            $this->indexer->installIndex($index);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->log(
                Logger::ERROR,
                'An unexpected exception occurred during the installation of an index. Error: {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /** {@inheritDoc} */
    public static function getSubscribedTopics(): array
    {
        return [
            GallyReindexFinishedTopic::getName(),
        ];
    }
}
