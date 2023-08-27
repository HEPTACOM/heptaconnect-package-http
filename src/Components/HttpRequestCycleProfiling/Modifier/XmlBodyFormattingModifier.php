<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier;

final class XmlBodyFormattingModifier extends AbstractBodyModifier
{
    protected function formatBodyContent(string $body): string
    {
        if (!\extension_loaded('dom')) {
            return $body;
        }

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($body);

        return (string) $dom->saveXML();
    }
}
