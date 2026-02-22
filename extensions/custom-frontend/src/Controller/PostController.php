<?php

namespace Commently\CustomFrontend\Controller;

use Commently\CustomFrontend\Request\CreatePostRequest;
use Flarum\Api\Client as ApiClient;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Single controller for posts: show (single post page) and store (create reply in discussion).
 */
class PostController
{
    public function __construct(
        protected ViewFactory $view,
        protected ApiClient $api,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function show(Request $request): ResponseInterface
    {
        $id = (int) ($request->getQueryParams()['id'] ?? 0);

        $html = $this->view->make('custom-frontend::posts.show', [
            'id' => $id,
            'translator' => $this->translator,
        ])->render();

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function store(Request $request): ResponseInterface
    {
        $discussionId = $request->getQueryParams()['id'] ?? null;
        
        if ($discussionId === null || $discussionId === '') {
            throw new \InvalidArgumentException('Discussion id is required');
        }

        $validated = CreatePostRequest::validate($request);

        $body = [
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'content' => $validated['content'],
                ],
                'relationships' => [
                    'discussion' => [
                        'data' => [
                            'type' => 'discussions',
                            'id' => (string) $discussionId,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->api
            ->withParentRequest($request)
            ->withBody($body)
            ->post('/posts');

        $bodyContents = $response->getBody()->getContents();

        if ($response->getStatusCode() !== 201) {
            throw new \RuntimeException('Failed to create post: ' . $bodyContents);
        }

        $data = json_decode($bodyContents, true);
        $postId = $data['data']['id'] ?? null;

        if (!$postId) {
            throw new \RuntimeException('Create post response missing post id');
        }

        $discussionUrl = $this->url->to('forum')->route('custom-frontend.discussion', ['id' => $discussionId]);

        return new RedirectResponse($discussionUrl);
    }
}
