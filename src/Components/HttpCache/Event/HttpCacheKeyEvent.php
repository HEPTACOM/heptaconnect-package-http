<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Event;

use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class HttpCacheKeyEvent extends Event
{
    public function __construct(
        private RequestInterface $request,
        private string $cacheKey,
    ) {
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function setCacheKey(string $cacheKey): void
    {
        $this->cacheKey = $cacheKey;
    }
}
