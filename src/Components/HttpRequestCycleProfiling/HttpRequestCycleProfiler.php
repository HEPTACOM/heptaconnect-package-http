<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpRequestCycleProfiler implements HttpRequestCycleProfilerInterface, HttpClientMiddlewareInterface
{
    /**
     * @var array<array-key, array{enabled: bool, collection: HttpRequestCycleCollector}>
     */
    private array $collections = [];

    public function with(\Closure $fn, HttpRequestCycleCollector $collection): mixed
    {
        $this->collections[] = [
            'enabled' => true,
            'collection' => $collection,
        ];

        try {
            return $fn();
        } finally {
            \array_pop($this->collections);
        }
    }

    public function without(\Closure $fn): mixed
    {
        $previousStates = [];

        foreach ($this->collections as $key => $collection) {
            $previousStates[$key] = $collection['enabled'];
            $this->collections[$key]['enabled'] = false;
        }

        try {
            return $fn();
        } finally {
            foreach ($previousStates as $key => $enabled) {
                $this->collections[$key]['enabled'] = $enabled;
            }
        }
    }

    public function process(RequestInterface $request, ClientInterface $handler): ResponseInterface
    {
        $enabledCollections = [];

        foreach ($this->collections as $key => $collection) {
            if ($collection['enabled']) {
                $enabledCollections[$key] = $collection['collection'];
            }
        }

        if ($enabledCollections === []) {
            return $handler->sendRequest($request);
        }

        $beginTime = \microtime(true);

        try {
            $response = $handler->sendRequest($request);
        } catch (\Throwable $exception) {
            $requestCycle = new HttpRequestCycle(
                $this->convertRequest($request),
                null
            );

            foreach ($enabledCollections as $collection) {
                $collection->add($requestCycle);
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

        $requestCycle = new HttpRequestCycle(
            $this->convertRequest($request),
            $response,
            $metadata
        );

        foreach ($enabledCollections as $collection) {
            $collection->add($requestCycle);
        }

        return $response;
    }

    private function convertRequest(RequestInterface $request): RequestInterface
    {
        $uri = $request->getUri()
            ->withScheme('')
            ->withUserInfo('')
            ->withHost('')
            ->withPort(null);

        return $request->withUri($uri);
    }

    private function convertTime(float $beginTime): string
    {
        return \date_create_from_format('U.u', (string) $beginTime)->format('Y-m-d H:i:s.v');
    }
}
