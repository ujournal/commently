<?php

namespace Commently\CustomFrontend\Controller;

use Carbon\Carbon;
use Commently\CustomFrontend\Request\CreateDiscussionRequest;
use Flarum\Api\Client as ApiClient;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Single controller for discussions: index, show, create form, and store.
 */
class DiscussionController
{
    public function __construct(
        protected ApiClient $api,
        protected ViewFactory $view,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function index(Request $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $sort = Arr::pull($queryParams, 'sort');
        $q = Arr::pull($queryParams, 'q');
        $page = max(1, (int) Arr::pull($queryParams, 'page'));
        $filters = Arr::pull($queryParams, 'filter', []);

        $sortMap = [
            'latest' => '-lastPostedAt',
            'top' => '-commentCount',
            'newest' => '-createdAt',
            'oldest' => 'createdAt',
        ];
        $params = [
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '-lastPostedAt',
            'filter' => $filters,
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20],
        ];

        if ($q) {
            $params['filter']['q'] = $q;
        }

        $apiDocument = $this->getApiDocument($request, $params);
        $hasNextPage = isset($apiDocument->links->next);

        return $this->response('custom-frontend::discussions.index', [
            'apiDocument' => $apiDocument,
            'page' => $page,
            'hasNextPage' => $hasNextPage,
            'url' => $this->url,
        ]);
    }

    public function show(Request $request): ResponseInterface
    {
        $id = $request->getQueryParams()['id'] ?? '';
        $page = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $params = [
            'id' => $id,
            'bySlug' => str_contains($id, '-'),
            'include' => 'posts,posts.user',
            'page' => [
                'offset' => ($page - 1) * 20,
                'limit' => 20,
            ],
        ];

        $response = $this->api
            ->withParentRequest($request)
            ->withQueryParams($params)
            ->get("/discussions/$id");

        if ($response->getStatusCode() === 404) {
            throw new RouteNotFoundException();
        }

        $apiDocument = json_decode($response->getBody()->getContents());

        $getResource = function ($link) use ($apiDocument) {
            return Arr::first($apiDocument->included ?? [], function ($value) use ($link) {
                return $value->type === $link->type && $value->id === (string) $link->id;
            });
        };

        $posts = [];

        foreach ($apiDocument->included ?? [] as $resource) {
            if ($resource->type === 'posts' && isset($resource->attributes->contentHtml)) {
                $posts[] = $resource;
            }
        }
        
        usort($posts, fn ($a, $b) => ($a->attributes->number ?? 0) <=> ($b->attributes->number ?? 0));

        $commentCount = (int) ($apiDocument->data->attributes->commentCount ?? 0);
        $hasPrevPage = $page > 1;
        $hasNextPage = $page < 1 + (int) ceil($commentCount / 20);

        $url = function (array $query) use ($id) {
            $path = $this->url->to('forum')->route('custom-frontend.discussion', ['id' => $id]);
            return $query ? $path . '?' . http_build_query($query) : $path;
        };

        $session = $request->getAttribute('session');
        $csrfToken = $session ? $session->token() : '';

        return $this->response('custom-frontend::discussions.show', [
            'apiDocument' => $apiDocument,
            'posts' => $posts,
            'page' => $page,
            'hasPrevPage' => $hasPrevPage,
            'hasNextPage' => $hasNextPage,
            'getResource' => $getResource,
            'url' => $url,
            'translator' => $this->translator,
            'csrfToken' => $csrfToken,
            'replyUrl' => $this->url->to('forum')->route('custom-frontend.posts.create', ['id' => $id]),
        ]);
    }

    public function create(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $csrfToken = $session ? $session->token() : '';

        $html = $this->view->make('custom-frontend::discussions.create', [
            'url' => $this->url,
            'csrfToken' => $csrfToken,
        ])->render();

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function store(Request $request): ResponseInterface
    {
        $validated = CreateDiscussionRequest::validate($request);
        $actor = RequestUtil::getActor($request);

        $title = $validated['title'];

        if ($title === null || $title === '') {
            $title = sprintf(
                'Discussion by @%s at %s',
                $actor->username,
                Carbon::now()->format('Y-m-d H:i')
            );
        }

        $body = [
            'data' => [
                'type' => 'discussions',
                'attributes' => [
                    'title' => $title,
                    'content' => $validated['content'],
                ],
            ],
        ];

        $response = $this->api
            ->withParentRequest($request)
            ->withBody($body)
            ->post('/discussions');

        $bodyContents = $response->getBody()->getContents();
        if ($response->getStatusCode() !== 201) {
            throw new \RuntimeException('Failed to create discussion: ' . $bodyContents);
        }

        $data = json_decode($bodyContents, true);
        $id = $data['data']['id'] ?? null;
        if (!$id) {
            throw new \RuntimeException('Create discussion response missing discussion id');
        }

        $discussionUrl = $this->url->to('forum')->route('custom-frontend.discussion', ['id' => $id]);

        return new RedirectResponse($discussionUrl);
    }

    protected function getApiDocument(Request $request, array $params): object
    {
        $response = $this->api
            ->withParentRequest($request)
            ->withQueryParams($params)
            ->get('/discussions');

        return json_decode($response->getBody()->getContents());
    }

    protected function response(string $viewName, array $data = []): ResponseInterface
    {
        $html = $this->view->make($viewName, $data)->render();

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
