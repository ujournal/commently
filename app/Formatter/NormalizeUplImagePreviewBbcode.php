<?php

namespace App\Formatter;

use s9e\TextFormatter\Parser;

/**
 * Ensures [upl-image-preview] BBCode attributes are quoted so the parser accepts them.
 * Unquoted url=https://... (and similar) can be mis-parsed or rejected; quoting fixes it.
 */
class NormalizeUplImagePreviewBbcode
{
    public function __invoke(Parser $parser, $context, string $text, $user = null): string
    {
        if (stripos($text, 'upl-image-preview') === false) {
            return $text;
        }

        $text = preg_replace_callback(
            '/\[upl-image-preview\s+([^\]]+)\]/iu',
            function (array $m): string {
                $inner = $m[1];
                if (preg_match('/\burl\s*=\s*["\']/', $inner)) {
                    return $m[0];
                }
                $inner = preg_replace(
                    '/\burl\s*=\s*(https?:\/\/[^\s\]]+)/iu',
                    'url="$1"',
                    $inner
                );
                return '[upl-image-preview ' . $inner . ']';
            },
            $text
        );

        return $text;
    }
}
