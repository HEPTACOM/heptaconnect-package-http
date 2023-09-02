<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier;

use Psr\Http\Message\StreamFactoryInterface;

final class JsonBodyFormattingModifier extends AbstractBodyModifier
{
    /**
     * @param string[] $mimeTypePattern
     */
    public function __construct(
        StreamFactoryInterface $streamFactory,
        ?int $lengthThreshold = null,
        bool $formatRequest = true,
        bool $formatResponse = true,
        bool $failSilent = true,
        array $mimeTypePattern = ['#^application/json.*#'],
        private ?int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES
    ) {
        parent::__construct(
            $streamFactory,
            $lengthThreshold,
            $formatRequest,
            $formatResponse,
            $failSilent,
            $mimeTypePattern
        );
    }

    protected function formatBodyContent(string $body): string
    {
        return (string) \json_encode(
            \json_decode($body, true, 512, \JSON_THROW_ON_ERROR),
            $this->jsonEncodeFlags | \JSON_THROW_ON_ERROR
        );
    }
}
