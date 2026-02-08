<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Http\Middleware\CheckCsrfToken;

return [
    // Disable CSRF checks for API requests (useful for API-only clients)
    (new Extend\Middleware('api'))
        ->remove(CheckCsrfToken::class),
    (new FoF\Upload\Extend\Adapters())
        ->force('aws-s3'),
];
