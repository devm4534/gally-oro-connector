<?php

namespace Gally\OroPlugin\EventListener;

use Gally\OroPlugin\Registry\SearchRegistry;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;

class WebsiteSearchAfterListener
{
    public function __construct(
        private SearchRegistry $registry,
    ) {
    }

    public function onSearchAfter(AfterSearchEvent $event): void
    {
        $this->registry->setAggregations($event->getResult()->getAggregatedData());
    }
}
