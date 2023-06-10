<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract;

/**
 * Profiler component to enable and disable HTTP request cycle recording
 */
interface HttpRequestCycleProfilerInterface
{
    /**
     * All outbound HTTP request cycles within the given closure are recorded in the given collector
     *
     * @template TResult
     *
     * @param \Closure(): TResult $fn
     *
     * @return TResult
     */
    public function with(\Closure $fn, HttpRequestCycleCollector $collector): mixed;

    /**
     * No outbound HTTP request cycles within the given closure are recorded.
     * This is useful to hide sensitive requests from a recording.
     *
     * @template TResult
     *
     * @param \Closure(): TResult $fn
     *
     * @return TResult
     */
    public function without(\Closure $fn): mixed;
}
