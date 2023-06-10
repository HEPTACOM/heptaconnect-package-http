<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

use Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\Psr7MessageSerializerInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type TRequestArray array{
 *     body: string,
 *     protocolVersion: string,
 *     headers: array<string|string[]>,
 *     type: 'request',
 *     uri: string,
 *     method: string,
 *     requestTarget: string
 * }
 * @phpstan-type TServerRequestArray array{
 *     body: string,
 *     protocolVersion: string,
 *     headers: array<string|string[]>,
 *     type: 'server-request',
 *     uri: string,
 *     method: string,
 *     serverParams: array,
 *     requestTarget: string,
 *     cookieParams: array,
 *     queryParams: array,
 *     uploadedFiles: array,
 *     parsedBody: null|array,
 *     attributes: array
 * }
 * @phpstan-type TResponseArray array{
 *     body: string,
 *     protocolVersion: string,
 *     headers: array<string|string[]>,
 *     type: 'response',
 *     statusCode: int,
 *     reasonPhrase: string
 * }
 */
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
            throw new \InvalidArgumentException(\sprintf('$message is neither a "%s", "%s" nor "%s"', ServerRequestInterface::class, RequestInterface::class, ResponseInterface::class));
        }

        $data['protocolVersion'] = $message->getProtocolVersion();
        $data['headers'] = $message->getHeaders();
        $data['body'] = (string) $message->getBody();

        return \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION);
    }

    public function deserialize(string $string): MessageInterface
    {
        $data = \json_decode($string, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($data)) {
            throw new \InvalidArgumentException('$string is not a JSON encoded object');
        }

        $type = $data['type'] ?? null;

        if ($type === 'request') {
            $data = $this->resolveRequestData($data);

            $message = $this->requestFactory->createRequest(
                $data['method'],
                $data['uri']
            );

            $message = $message->withRequestTarget($data['requestTarget']);
        } elseif ($type === 'server-request') {
            $data = $this->resolveServerRequestData($data);

            $message = $this->serverRequestFactory->createServerRequest(
                $data['method'],
                $data['uri'],
                $data['serverParams']
            );

            $message = $message->withRequestTarget($data['requestTarget']);
            $message = $message->withCookieParams($data['cookieParams']);
            $message = $message->withQueryParams($data['queryParams']);
            // TODO test files
            $message = $message->withUploadedFiles($data['uploadedFiles']);
            $message = $message->withParsedBody($data['parsedBody']);

            foreach ($data['attributes'] as $attributeName => $attributeValue) {
                $message = $message->withAttribute((string) $attributeName, $attributeValue);
            }
        } elseif ($type === 'response') {
            $data = $this->resolveResponseData($data);

            $message = $this->responseFactory->createResponse(
                $data['statusCode'],
                $data['reasonPhrase']
            );
        } else {
            throw new \InvalidArgumentException();
        }

        $message = $message->withProtocolVersion($data['protocolVersion']);

        foreach ($data['headers'] as $headerName => $headerValue) {
            $message = $message->withHeader((string) $headerName, $headerValue);
        }

        $message = $message->withBody(
            $this->streamFactory->createStream($data['body'])
        );

        return $message;
    }

    private function createServerRequestResolver(): OptionsResolver
    {
        $options = $this->createRequestResolver();

        $options->setAllowedValues('type', 'server-request');
        $options->define('type')->required()->allowedTypes('string')->allowedValues(['request']);
        $options->define('serverParams')->required()->allowedTypes('array');
        $options->define('cookieParams')->required()->allowedTypes('array');
        $options->define('queryParams')->required()->allowedTypes('array');
        $options->define('uploadedFiles')->required()->allowedTypes('array');
        $options->define('attributes')->required()->allowedTypes('array');
        $options->define('parsedBody')->required()->allowedTypes('array', 'null');

        return $options;
    }

    private function createRequestResolver(): OptionsResolver
    {
        $options = $this->createMessageResolver();

        $options->define('type')->required()->allowedTypes('string')->allowedValues(['request']);
        $options->define('uri')->required()->allowedTypes('string');
        $options->define('method')->required()->allowedTypes('string');
        $options->define('requestTarget')->required()->allowedTypes('string');

        return $options;
    }

    private function createResponseResolver(): OptionsResolver
    {
        $options = $this->createMessageResolver();

        $options->define('type')->required()->allowedTypes('string')->allowedValues(['response']);
        $options->define('statusCode')->required()->allowedTypes('integer');
        $options->define('reasonPhrase')->required()->allowedTypes('string');

        return $options;
    }

    private function createMessageResolver(): OptionsResolver
    {
        $options = new OptionsResolver();

        $options->define('body')->required()->allowedTypes('string');
        $options->define('protocolVersion')->required()->allowedTypes('string');
        $options->define('headers')->required()->allowedTypes('array')
            ->normalize(function (Options $_, array $value): array {
                foreach ($value as $item) {
                    if (!\is_string($item) && !\is_array($item)) {
                        throw new \InvalidArgumentException('Header values must be a string or an array of strings');
                    }

                    if (\is_array($item)) {
                        foreach ($item as $nestedItem) {
                            if (!\is_string($nestedItem)) {
                                throw new \InvalidArgumentException('Nested header values must be strings');
                            }
                        }
                    }
                }

                return $value;
            });

        return $options;
    }

    /**
     * @return TRequestArray
     */
    private function resolveRequestData(array $data): array
    {
        $options = $this->createRequestResolver();
        /** @var TRequestArray $result */
        $result = $options->resolve($data);

        return $result;
    }

    /**
     * @return TServerRequestArray
     */
    private function resolveServerRequestData(array $data): array
    {
        $options = $this->createServerRequestResolver();
        /** @var TServerRequestArray $result */
        $result = $options->resolve($data);

        return $result;
    }

    /**
     * @return TResponseArray
     */
    private function resolveResponseData(array $data): array
    {
        $options = $this->createResponseResolver();
        /** @var TResponseArray $result */
        $result = $options->resolve($data);

        return $result;
    }
}
