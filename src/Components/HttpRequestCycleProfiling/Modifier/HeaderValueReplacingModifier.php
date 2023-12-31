<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier;

use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycle;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleModifierInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HeaderValueReplacingModifier implements HttpRequestCycleModifierInterface
{
    /**
     * @param array<string, array{string, string}> $replacementPatterns
     */
    public function __construct(
        private array $replacementPatterns,
        private bool $replaceRequest = true,
        private bool $replaceResponse = true,
    ) {
    }

    public function modify(HttpRequestCycle $httpRequestCycle): HttpRequestCycle
    {
        $request = $this->replaceRequest($httpRequestCycle->getRequest());
        $response = $this->replaceResponse($httpRequestCycle->getResponse());

        return new HttpRequestCycle($request, $response, $httpRequestCycle->getMetadata());
    }

    private function replaceRequest(RequestInterface $request): RequestInterface
    {
        if (!$this->replaceRequest) {
            return $request;
        }

        return $this->replaceMessage($request);
    }

    private function replaceResponse(?ResponseInterface $response = null): ?ResponseInterface
    {
        if (!$this->replaceResponse) {
            return $response;
        }

        if ($response === null) {
            return $response;
        }

        return $this->replaceMessage($response);
    }

    /**
     * @template T of MessageInterface
     *
     * @param T $message
     *
     * @return T
     */
    private function replaceMessage(mixed $message): mixed
    {
        foreach ($this->replacementPatterns as $key => [$match, $replace]) {
            foreach (\array_keys($message->getHeaders()) as $headerName) {
                if (\preg_match($key, (string) $headerName) === 1) {
                    $headers = $message->getHeader((string) $headerName);

                    foreach ($headers as &$headerValue) {
                        $headerValue = \preg_replace($match, $replace, $headerValue);
                    }

                    $message = $message->withHeader((string) $headerName, $headers);
                }
            }
        }

        return $message;
    }
}
