<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpRequestCycle
{
    public function __construct(
        private RequestInterface $request,
        private ?ResponseInterface $response,
        private array $metadata = [],
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
