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
     * @var HttpRequestCycleModifierInterface[]
     */
    private array $modifiers = [];

    /**
     * @return HttpRequestCycle[]
     */
    public function collect(): array
    {
        return $this->httpRequestCycles;
    }

    public function add(HttpRequestCycle $httpRequestCycle): void
    {
        foreach ($this->modifiers as $modifier) {
            $httpRequestCycle = $modifier->modify($httpRequestCycle);
        }

        $this->httpRequestCycles[] = $httpRequestCycle;
    }

    /**
     * @return HttpRequestCycleModifierInterface[]
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function withAddedModifier(HttpRequestCycleModifierInterface ...$modifiers): self
    {
        $that = clone $this;

        foreach ($modifiers as $modifier) {
            $that->modifiers[] = $modifier;
        }

        return $that;
    }

    public function withoutModifiers(): self
    {
        $that = clone $this;
        $that->modifiers = [];

        return $that;
    }
}
