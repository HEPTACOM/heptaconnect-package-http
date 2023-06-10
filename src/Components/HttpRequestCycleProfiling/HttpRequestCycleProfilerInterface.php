<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling;

interface HttpRequestCycleProfilerInterface
{
    public function with(\Closure $fn, HttpRequestCycleCollector $collection): mixed;

    public function without(\Closure $fn): mixed;
}
