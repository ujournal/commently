<?php

namespace Commently\CustomFrontend;

use Flarum\Extend;

return [
    (new Extend\ServiceProvider)
        ->register(ForumRoutesServiceProvider::class),

    (new Extend\View)
        ->namespace('custom-frontend', __DIR__.'/src/resources/views'),
];
