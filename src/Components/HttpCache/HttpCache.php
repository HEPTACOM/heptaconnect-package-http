<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

use Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Event\HttpCacheActiveEvent;
use Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Event\HttpCacheKeyEvent;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpCache implements HttpCacheInterface, HttpClientMiddlewareInterface
{
    public const KEY_PREFIX = 'http-cache:';

    public function __construct(
        private Psr7MessageSerializerInterface $messageSerializer,
        private PortalStorageInterface $portalStorage,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(RequestInterface $request, ClientInterface $handler): ResponseInterface
    {
        $cacheKey = self::KEY_PREFIX . $this->getCacheKey($request);

        /** @var HttpCacheActiveEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new HttpCacheActiveEvent($request)
        );

        if ($event->isActive()) {
            $response = $this->loadResponse($cacheKey);

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        $response = $handler->sendRequest($request);

        /** @var HttpCacheActiveEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new HttpCacheActiveEvent($request, $response)
        );

        if ($event->isActive()) {
            $this->saveResponse($cacheKey, $response, $event->getTtl());
        }

        return $response;
    }

    public function clear(): void
    {
        /** @var iterable<string> $keys */
        $keys = (function (): iterable {
            foreach ($this->portalStorage->list() as $key => $_) {
                if (!\is_string($key)) {
                    continue;
                }

                if (\str_starts_with($key, self::KEY_PREFIX)) {
                    yield $key;
                }
            }
        })();

        $this->portalStorage->deleteMultiple($keys);
    }

    private function loadResponse(string $cacheKey): ?ResponseInterface
    {
        $serializedResponse = $this->portalStorage->get($cacheKey);

        if (!\is_string($serializedResponse)) {
            return null;
        }

        /** @var ResponseInterface $response */
        $response = $this->messageSerializer->deserialize($serializedResponse);

        return $response;
    }

    private function saveResponse(string $cacheKey, ResponseInterface $response, ?\DateInterval $ttl): void
    {
        $serializedResponse = $this->messageSerializer->serialize($response);

        $this->portalStorage->set($cacheKey, $serializedResponse, $ttl);
    }

    private function getCacheKey(RequestInterface $request): string
    {
        /** @var HttpCacheKeyEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new HttpCacheKeyEvent($request, (string) $request->getUri())
        );

        return $event->getCacheKey();
    }
}
