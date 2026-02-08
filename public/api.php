<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

$site = require '../site.php';

/*
|-------------------------------------------------------------------------------
| Accept incoming HTTP requests (API only)
|-------------------------------------------------------------------------------
|
| Every HTTP request pointed to the web server that cannot be served by simply
| responding with one of the files in the "public" directory will be sent to
| this file. Serves only the JSON API; no forum or admin frontend.
|
*/

$app = $site->bootApp();
$container = $app->getContainer();
$config = $container->make('flarum.config');

// Build API-only pipeline
$basePath = $config->url()->getPath() ?: '/';
$apiPath = '/' . ($config['paths']['api'] ?? 'api');
$apiBasePath = rtrim($basePath, '/') . $apiPath;
if ($apiBasePath === '') {
    $apiBasePath = '/';
}

$pipe = new Laminas\Stratigility\MiddlewarePipe;
$pipe->pipe(new Flarum\Http\Middleware\ProcessIp());
$pipe->pipe(new Middlewares\BasePath($apiBasePath));
$pipe->pipe(new Laminas\Stratigility\Middleware\OriginalMessages());
$pipe->pipe(
    new Middlewares\BasePathRouter([
        '/' => 'flarum.api.handler',
    ])
);
$pipe->pipe(new Middlewares\RequestHandler($container));

$runner = new Laminas\HttpHandlerRunner\RequestHandlerRunner(
    $pipe,
    new Laminas\HttpHandlerRunner\Emitter\SapiEmitter,
    [Laminas\Diactoros\ServerRequestFactory::class, 'fromGlobals'],
    function (Throwable $e) {
        $generator = new Laminas\Stratigility\Middleware\ErrorResponseGenerator;
        return $generator($e, new Laminas\Diactoros\ServerRequest, new Laminas\Diactoros\Response);
    }
);
$runner->run();
