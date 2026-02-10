<?php

namespace App\Formatter;

use DOMDocument;
use s9e\TextFormatter\Renderer;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Strips FoF Upload BBCode tags (UPL-TEXT-PREVIEW, UPL-IMAGE-PREVIEW) that have no uuid
 * attribute before FoF's formatters run. Prevents TypeError when FoF's FormatTextPreview
 * calls findByUuid(null), and avoids render failures.
 */
class SanitizeUploadTags
{
    private const TAGS = ['UPL-TEXT-PREVIEW', 'UPL-IMAGE-PREVIEW'];

    public function __invoke(Renderer $renderer, $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        if (strpos($xml, 'UPL-') === false) {
            return $xml;
        }

        $useErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $wrapped = '<r>' . $xml . '</r>';
        $loaded = $dom->loadXML($wrapped, LIBXML_NOERROR | LIBXML_COMPACT);
        libxml_use_internal_errors($useErrors);

        if (!$loaded) {
            return $xml;
        }

        $changed = false;
        foreach (self::TAGS as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);
            $toRemove = [];
            foreach ($elements as $el) {
                $uuid = $el->getAttribute('uuid');
                if ($uuid === '' || $uuid === null) {
                    $toRemove[] = $el;
                }
            }
            foreach ($toRemove as $el) {
                if ($el->parentNode) {
                    $el->parentNode->removeChild($el);
                    $changed = true;
                }
            }
        }

        if (!$changed) {
            return $xml;
        }

        $inner = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $inner .= $dom->saveXML($child);
        }

        return $inner;
    }
}
