<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr7MessageSerializer implements Psr7MessageSerializerInterface
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private ServerRequestFactoryInterface $serverRequestFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function serialize(MessageInterface $message): string
    {
        $data = [];

        if ($message instanceof RequestInterface) {
            $data['type'] = 'request';
            $data['requestTarget'] = $message->getRequestTarget();
            $data['method'] = $message->getMethod();
            $data['uri'] = (string) $message->getUri();

            if ($message instanceof ServerRequestInterface) {
                $data['type'] = 'server-request';
                $data['serverParams'] = $message->getServerParams();
                $data['cookieParams'] = $message->getCookieParams();
                $data['queryParams'] = $message->getQueryParams();
                $data['uploadedFiles'] = $message->getUploadedFiles();
                $data['parsedBody'] = $message->getParsedBody();
                $data['attributes'] = $message->getAttributes();
            }
        } elseif ($message instanceof ResponseInterface) {
            $data['type'] = 'response';
            $data['statusCode'] = $message->getStatusCode();
            $data['reasonPhrase'] = $message->getReasonPhrase();
        } else {
            throw new \InvalidArgumentException();
        }

        $data['protocolVersion'] = $message->getProtocolVersion();
        $data['headers'] = $message->getHeaders();
        $data['body'] = (string) $message->getBody();

        $string = \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION);

        return $string;
    }

    public function deserialize(string $string): MessageInterface
    {
        $data = \json_decode($string, true, 512, \JSON_THROW_ON_ERROR);

        $type = $data['type'] ?? null;

        if ($type == 'request') {
            $message = $this->requestFactory->createRequest(
                $data['method'],
                $data['uri']
            );

            $message = $message->withRequestTarget($data['requestTarget']);
        } elseif ($type == 'server-request') {
            $message = $this->serverRequestFactory->createServerRequest(
                $data['method'],
                $data['uri'],
                $data['serverParams']
            );

            $message = $message->withRequestTarget($data['requestTarget']);
            $message = $message->withCookieParams($data['cookieParams']);
            $message = $message->withQueryParams($data['queryParams']);
            $message = $message->withUploadedFiles($data['uploadedFiles']);
            $message = $message->withParsedBody($data['parsedBody']);

            foreach ($data['attributes'] as $attributeName => $attributeValue) {
                $message = $message->withAttribute($attributeName, $attributeValue);
            }
        } elseif ($type == 'response') {
            $message = $this->responseFactory->createResponse(
                $data['statusCode'],
                $data['reasonPhrase']
            );
        } else {
            throw new \InvalidArgumentException();
        }

        $message = $message->withProtocolVersion($data['protocolVersion']);

        foreach ($data['headers'] as $headerName => $headerValue) {
            $message = $message->withHeader($headerName, $headerValue);
        }

        $message = $message->withBody(
            $this->streamFactory->createStream($data['body'])
        );

        return $message;
    }
}
