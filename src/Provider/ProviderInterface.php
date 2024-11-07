<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Provider;

/**
 * Gally data provider interface.
 */
interface ProviderInterface
{
    public function provide(): iterable;
}
