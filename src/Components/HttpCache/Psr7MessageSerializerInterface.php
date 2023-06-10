<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

use Psr\Http\Message\MessageInterface;

interface Psr7MessageSerializerInterface
{
    public function serialize(MessageInterface $message): string;

    public function deserialize(string $string): MessageInterface;
}
