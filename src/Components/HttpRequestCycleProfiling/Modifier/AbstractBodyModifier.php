<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier;

use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycle;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleModifierInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractBodyModifier implements HttpRequestCycleModifierInterface
{
    /**
     * @param string[] $mimeTypePattern
     */
    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private ?int $lengthThreshold = null,
        private bool $formatRequest = true,
        private bool $formatResponse = true,
        private bool $failSilent = true,
        private array $mimeTypePattern = ['#^application/json.*#']
    ) {
    }

    public function modify(HttpRequestCycle $httpRequestCycle): HttpRequestCycle
    {
        $request = $this->formatRequest($httpRequestCycle->getRequest());
        $response = $this->formatResponse($httpRequestCycle->getResponse());

        return new HttpRequestCycle($request, $response, $httpRequestCycle->getMetadata());
    }

    /**
     * Modify the body content.
     */
    abstract protected function formatBodyContent(string $body): string;

    private function passContentLengthThresholdTest(MessageInterface $message): bool
    {
        $lengthThreshold = $this->lengthThreshold;

        if ($lengthThreshold === null || $lengthThreshold < 0) {
            return true;
        }

        $contentLengthHeader = $message->getHeaderLine('Content-Length');

        if (\is_numeric($contentLengthHeader)) {
            $contentLength = (int) $contentLengthHeader;
        } else {
            $contentLength = $message->getBody()->getSize() ?? \strlen((string) $message->getBody());
        }

        if ($contentLength === 0) {
            return false;
        }

        return $lengthThreshold > $contentLength;
    }

    private function formatBody(StreamInterface $stream, ?int &$newLength): StreamInterface
    {
        $body = (string) $stream;

        try {
            $formatted = $this->formatBodyContent($body);

            if ($formatted === $body) {
                return $stream;
            }

            $newLength = \strlen($formatted);
            $stream = $this->streamFactory->createStream($formatted);
        } catch (\Throwable $throwable) {
            if (!$this->failSilent) {
                throw $throwable;
            }
        }

        return $stream;
    }

    private function passContentTypeTest(string $contentType): bool
    {
        foreach ($this->mimeTypePattern as $mimeTypePattern) {
            if (\preg_match($mimeTypePattern, $contentType) === 1) {
                return true;
            }
        }

        return false;
    }

    private function formatRequest(RequestInterface $request): RequestInterface
    {
        if (!$this->formatRequest) {
            return $request;
        }

        return $this->formatMessage($request);
    }

    private function formatResponse(?ResponseInterface $response = null): ?ResponseInterface
    {
        if (!$this->formatResponse) {
            return $response;
        }

        if ($response === null) {
            return $response;
        }

        return $this->formatMessage($response);
    }

    /**
     * @template T of MessageInterface
     *
     * @param T $message
     *
     * @return T
     */
    private function formatMessage(mixed $message): mixed
    {
        if (!$this->passContentLengthThresholdTest($message)) {
            return $message;
        }

        $responseContentType = $message->getHeaderLine('Content-Type');

        if (!$this->passContentTypeTest($responseContentType)) {
            return $message;
        }

        $message = $message->withBody($this->formatBody($message->getBody(), $newLength));

        if ($newLength === null || !$message->hasHeader('Content-Length')) {
            return $message;
        }

        return $message->withHeader('Content-Length', [(string) $newLength]);
    }
}
