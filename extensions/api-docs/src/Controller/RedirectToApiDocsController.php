<?php

namespace Commently\ApiDocs\Controller;

use Flarum\Foundation\Config;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Redirects /docs to /api/docs so the docs can live at the "docs" route.
 * Uses the current request's URI so host/port/scheme are preserved (avoids ERR_INVALID_REDIRECT).
 */
class RedirectToApiDocsController implements RequestHandlerInterface
{
    public function __construct(
        protected Config $config
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $path = $this->config->offsetGet('paths') ?? [];
        $apiPath = trim($path['api'] ?? 'api', '/');
        $apiDocsPath = ($apiPath !== '' ? $apiPath . '/' : '') . 'docs';

        $uri = $request->getUri();
        $to = (string) $uri->withPath('/' . $apiDocsPath)->withQuery('')->withFragment('');

        return new RedirectResponse($to, 302);
    }
}
