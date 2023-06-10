<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpCache;

use Psr\Http\Message\MessageInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * Describes a service, that can convert PSR-7 messages to a string and back.
 */
interface Psr7MessageSerializerInterface
{
    /**
     * Serializes the given message into a string, that can be deserialized later with @see Psr7MessageSerializerInterface::deserialize()
     *
     * @throws \JsonException If the message contains not JSON-compatible data
     */
    public function serialize(MessageInterface $message): string;

    /**
     * Deserializes the given string into a @see MessageInterface
     * The result type depends on the serialized type.
     * The input is expected to be the result of @see Psr7MessageSerializerInterface::serialize()
     *
     * @throws \InvalidArgumentException If the decoded string is neither an object nor has a valid type
     * @throws \JsonException            If the string contains not JSON-compatible data
     * @throws UndefinedOptionsException If the decoded string has a case, where an option name is undefined
     * @throws InvalidOptionsException   If the decoded string has a case, where an option doesn't fulfill the specified validation rules
     * @throws MissingOptionsException   If the decoded string has a case, where a required option is missing
     * @throws OptionDefinitionException If the decoded string has a case, where there is a cyclic dependency between lazy options and/or normalizers
     * @throws NoSuchOptionException     If the decoded string has a case, where a lazy option reads an unavailable option
     * @throws AccessException           If the decoded string has a case, where called from a lazy option or normalizer
     */
    public function deserialize(string $string): MessageInterface;
}
