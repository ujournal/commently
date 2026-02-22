<?php

namespace Commently\CustomFrontend\Controller;

use Flarum\Api\Client as ApiClient;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Renders the list of primary tags as a turbo-frame for the left nav.
 */
class TagController
{
    public function __construct(
        protected ApiClient $api,
        protected ViewFactory $view,
        protected UrlGenerator $url
    ) {
    }

    public function index(Request $request): ResponseInterface
    {
        $primaryTags = $this->getPrimaryTags($request);
        $html = $this->view->make('custom-frontend::tags.index', [
            'primaryTags' => $primaryTags,
            'url' => $this->url,
        ])->render();

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Primary tags (top-level, no parent).
     *
     * @return list<array{id: string, type: string, attributes: array}>
     */
    protected function getPrimaryTags(Request $request): array
    {
        $response = $this->api
            ->withParentRequest($request)
            ->get('/tags');

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $document = json_decode($response->getBody()->getContents(), true);
        $data = $document['data'] ?? [];

        return array_values(array_filter($data, function ($resource) {
            $attrs = $resource['attributes'] ?? [];
            return empty($attrs['isChild'] ?? true);
        }));
    }
}
