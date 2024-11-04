<?php

namespace Gally\OroPlugin\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;

/**
 * Add web catalog node related data to search index
 */
class WebsiteSearchSaveAggregationsListener
{
    public array $aggregations = []; // Todo private

    public function __construct(
    ) {
    }

    public function onSearchAfter(AfterSearchEvent $event): void
    {
        // Todo manage partial update ?
        $this->aggregations = $event->getResult()->getAggregatedData();
    }
}
