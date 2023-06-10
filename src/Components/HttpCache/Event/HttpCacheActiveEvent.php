<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Event;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class HttpCacheActiveEvent extends Event
{
    private ?\DateInterval $ttl = null;

    private bool $active = false;

    public function __construct(
        private RequestInterface $request,
        private ?ResponseInterface $response = null,
    ) {
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getTtl(): ?\DateInterval
    {
        return $this->ttl;
    }

    public function setTtl(?\DateInterval $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isMethod(string ...$method): bool
    {
        $requestMethod = \strtoupper($this->getRequest()->getMethod());

        return \in_array($requestMethod, $method, true);
    }

    public function isUri(UriInterface ...$uri): bool
    {
        $actualUri = (string) $this->getRequest()->getUri();

        foreach ($uri as $expectedUri) {
            if ((string) $expectedUri === $actualUri) {
                return true;
            }
        }

        return false;
    }
}
