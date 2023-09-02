<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract;

/**
 * Describes class, that can modify an HTTP request cycle before storing in a @see HttpRequestCycleCollector
 */
interface HttpRequestCycleModifierInterface
{
    /**
     * Returns the same or a modified instance of the given @param $httpRequestCycle.
     */
    public function modify(HttpRequestCycle $httpRequestCycle): HttpRequestCycle;
}
