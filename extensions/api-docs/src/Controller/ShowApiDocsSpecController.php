<?php

namespace Commently\ApiDocs\Controller;

use Commently\ApiDocs\OpenApiSpecBuilder;
use Flarum\Foundation\Config;
use Flarum\Http\RouteCollection;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ShowApiDocsSpecController implements RequestHandlerInterface
{
    public function __construct(
        protected Container $container,
        protected Config $config
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        /** @var RouteCollection $apiRoutes */
        $apiRoutes = $this->container->make('flarum.api.routes');

        $baseUrl = (string) $request->getUri()->withPath('')->withQuery('')->withFragment('');
        $paths = $this->config->offsetGet('paths') ?? [];
        $apiPath = $paths['api'] ?? 'api';

        $builder = new OpenApiSpecBuilder($apiRoutes, $baseUrl, $apiPath);
        $spec = $builder->build();

        return new JsonResponse($spec, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
