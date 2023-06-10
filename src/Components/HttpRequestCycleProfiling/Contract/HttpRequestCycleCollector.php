<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract;

final class HttpRequestCycleCollector
{
    /**
     * @var HttpRequestCycle[]
     */
    private array $httpRequestCycles = [];

    /**
     * @return HttpRequestCycle[]
     */
    public function collect(): array
    {
        return $this->httpRequestCycles;
    }

    public function add(HttpRequestCycle $httpRequestCycle): void
    {
        $this->httpRequestCycles[] = $httpRequestCycle;
    }
}
