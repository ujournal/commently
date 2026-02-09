<?php

namespace Commently\ApiDocs\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Serves the Swagger UI HTML page that loads the OpenAPI spec from docs.json.
 */
class ShowApiDocsUiController implements RequestHandlerInterface
{
    public function handle(Request $request): ResponseInterface
    {
        // Spec URL relative to current path: we're at /api/docs, spec is /api/docs.json
        $specUrl = 'docs.json';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "{$specUrl}",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
