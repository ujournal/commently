<?php

namespace Commently\ApiDocs;

use Commently\ApiDocs\Controller\RedirectToApiDocsController;
use Commently\ApiDocs\Controller\ShowApiDocsSpecController;
use Commently\ApiDocs\Controller\ShowApiDocsUiController;
use Flarum\Extend;

return [
    (new Extend\Routes('api'))
        ->get('/docs', 'api-docs.ui', ShowApiDocsUiController::class)
        ->get('/docs.json', 'api-docs.spec', ShowApiDocsSpecController::class),

    (new Extend\Routes('forum'))
        ->get('/docs', 'api-docs.redirect', RedirectToApiDocsController::class),
];
