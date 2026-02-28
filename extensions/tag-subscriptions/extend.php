<?php

namespace Commently\TagSubscriptions;

use Flarum\Extend;

return [
    (new Extend\Routes('api'))
        ->get('/tag-subscriptions', 'tag-subscriptions.index', Api\Controller\ListTagSubscriptionsController::class)
        ->put('/tag-subscriptions', 'tag-subscriptions.update', Api\Controller\UpdateTagSubscriptionsController::class),
];
