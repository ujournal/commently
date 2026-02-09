<?php

namespace Commently\DatabaseSession;

use Flarum\Extend;

return [
    (new Extend\ServiceProvider())
        ->register(ServiceProvider::class),
];
