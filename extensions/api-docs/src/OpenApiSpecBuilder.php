<?php

namespace Commently\ApiDocs;

use Flarum\Http\RouteCollection;

/**
 * Builds an OpenAPI 3.0 specification from Flarum's API route collection.
 */
class OpenApiSpecBuilder
{
    public function __construct(
        protected RouteCollection $routes,
        protected string $baseUrl,
        protected string $apiPath = 'api'
    ) {
    }

    public function build(): array
    {
        $paths = [];
        foreach ($this->routes->getRoutes() as $name => $route) {
            $method = strtolower($route['method']);
            $path = $this->toOpenApiPath($route['path']);
            if (! isset($paths[$path])) {
                $paths[$path] = [];
            }
            $summary = $this->routeNameToSummary($name);
            $paths[$path][$method] = [
                'summary' => $summary,
                'operationId' => $this->routeNameToOperationId($name),
                'tags' => [$this->routeNameToTag($name)],
                'responses' => [
                    '200' => ['description' => 'Success'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden'],
                    '404' => ['description' => 'Not found'],
                ],
            ];
        }

        $serverUrl = rtrim($this->baseUrl, '/') . '/' . trim($this->apiPath, '/');

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Flarum API',
                'description' => 'Auto-generated API documentation for this Flarum forum. Uses JSON:API format.',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => $serverUrl],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Token',
                        'description' => 'Authorization header: Bearer {token}. Use the /api/token endpoint to obtain a token.',
                    ],
                ],
                'security' => [['bearerAuth' => []]],
            ],
        ];
    }

    protected function toOpenApiPath(string $path): string
    {
        // Flarum paths are like /users/{id}; FastRoute uses {id} already.
        return '/' . ltrim($path, '/');
    }

    protected function routeNameToSummary(string $name): string
    {
        $parts = explode('.', $name);
        $action = end($parts);
        $resource = implode(' ', array_slice($parts, 0, -1));
        $actions = [
            'index' => 'List',
            'show' => 'Get',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
        ];
        $verb = $actions[$action] ?? ucfirst($action);
        return $verb . ($resource ? ' ' . $resource : '');
    }

    protected function routeNameToOperationId(string $name): string
    {
        return str_replace('.', '_', $name);
    }

    protected function routeNameToTag(string $name): string
    {
        $parts = explode('.', $name);
        array_pop($parts); // remove action
        return implode(' ', array_map('ucfirst', $parts)) ?: 'API';
    }
}
