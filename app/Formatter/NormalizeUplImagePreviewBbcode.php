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
        // Only run if the tag might be present
        if (stripos($text, 'upl-image-preview') === false) {
            return $text;
        }

        // Wrap unquoted url= values in double quotes so the BBCode parser accepts long URLs
        $text = preg_replace_callback(
            '/\[upl-image-preview\s+([^\]]+)\]/iu',
            function (array $m): string {
                $inner = $m[1];
                // If url= is already quoted, leave as-is
                if (preg_match('/\burl\s*=\s*["\']/', $inner)) {
                    return $m[0];
                }
                // Wrap unquoted url=... in quotes (value = rest of attribute until space + next attr or end)
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
