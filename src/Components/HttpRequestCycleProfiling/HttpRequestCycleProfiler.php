<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling;

use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycle;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleCollector;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleModifierInterface;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleProfilerInterface;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier\RequestUrlModifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpRequestCycleProfiler implements HttpRequestCycleProfilerInterface, HttpClientMiddlewareInterface
{
    /**
     * @var array<array-key, array{enabled: bool, collector: HttpRequestCycleCollector}>
     */
    private array $collectors = [];

    private HttpRequestCycleModifierInterface $fallbackModifier;

    public function __construct()
    {
        $this->fallbackModifier = new RequestUrlModifier(
            ['/.*/', ''],
            ['/.*/', ''],
            ['/.*/', ''],
            ['/.*/', '']
        );
    }

    public function with(\Closure $fn, HttpRequestCycleCollector $collector): mixed
    {
        $this->collectors[] = [
            'enabled' => true,
            'collector' => $collector,
        ];

        try {
            return $fn();
        } finally {
            \array_pop($this->collectors);
        }
    }

    public function without(\Closure $fn): mixed
    {
        $previousStates = [];

        foreach ($this->collectors as $key => $collector) {
            $previousStates[$key] = $collector['enabled'];
            $this->collectors[$key]['enabled'] = false;
        }

        try {
            return $fn();
        } finally {
            foreach ($previousStates as $key => $enabled) {
                $this->collectors[$key]['enabled'] = $enabled;
            }
        }
    }

    public function process(RequestInterface $request, ClientInterface $handler): ResponseInterface
    {
        $enabledCollectors = [];

        foreach ($this->collectors as $key => $collector) {
            if ($collector['enabled']) {
                $enabledCollectors[$key] = $collector['collector'];
            }
        }

        if ($enabledCollectors === []) {
            return $handler->sendRequest($request);
        }

        $beginTime = \microtime(true);

        try {
            $response = $handler->sendRequest($request);
        } catch (\Throwable $exception) {
            $requestCycle = new HttpRequestCycle($request, null);

            foreach ($enabledCollectors as $collector) {
                if ($collector->getModifiers() === []) {
                    $collector->add($this->fallbackModifier->modify($requestCycle));
                } else {
                    $collector->add($requestCycle);
                }
            }

            throw $exception;
        }

        $endTime = \microtime(true);
        $duration = $endTime - $beginTime;

        $metadata = [
            'Duration' => \round($duration, 3) . ' seconds',
            'Time start' => $this->convertTime($beginTime),
            'Time finish' => $this->convertTime($endTime),
        ];

        $requestCycle = new HttpRequestCycle($request, $response, $metadata);

        foreach ($enabledCollectors as $collector) {
            if ($collector->getModifiers() === []) {
                $collector->add($this->fallbackModifier->modify($requestCycle));
            } else {
                $collector->add($requestCycle);
            }
        }

        return $response;
    }

    private function convertTime(float $beginTime): string
    {
        return \date_create_from_format('U.u', (string) $beginTime)->format('Y-m-d H:i:s.v');
    }
}
