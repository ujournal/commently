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
use Illuminate\Support\Str;
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
        $tagParam = Arr::pull($queryParams, 'tag');

        $primaryTags = $this->getPrimaryTags($request);
        $subscribedTagSlugs = $this->getSubscribedTagSlugs($request);
        $subscriptionApiAvailable = $this->isTagSubscriptionsApiAvailable($request);

        // Filter slugs: URL ?tag=slug1,slug2 overrides; otherwise use subscribed tags
        $filterSlugs = $this->parseTagFilter($tagParam);
        if ($filterSlugs === []) {
            $filterSlugs = $subscribedTagSlugs;
        }

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
            'include' => 'user,tags,firstPost',
        ];

        if ($q) {
            $params['filter']['q'] = $q;
        }
        if ($filterSlugs !== []) {
            $params['filter']['tag'] = implode(',', $filterSlugs);
        }

        $apiDocument = $this->getApiDocument($request, $params);
        $hasNextPage = isset($apiDocument->links->next);

        $getResource = function ($link) use ($apiDocument) {
            return Arr::first($apiDocument->included ?? [], function ($value) use ($link) {
                return $value->type === $link->type && $value->id === (string) $link->id;
            });
        };

        $getDiscussionExcerpt = function ($discussion) use ($getResource): ?string {
            $firstPostLink = $discussion->relationships->firstPost->data ?? null;
            if (! $firstPostLink) {
                return null;
            }
            $post = $getResource($firstPostLink);
            if (! $post) {
                return null;
            }
            $contentHtml = $post->attributes->contentHtml ?? '';
            if ($contentHtml === '') {
                return null;
            }
            $text = trim(strip_tags($contentHtml));
            if ($text === '') {
                return null;
            }
            $firstParagraph = trim((array_values(preg_split('/\s*\n\s*\n\s*/u', $text, 2)))[0] ?? $text);
            $maxLength = 160;
            if (Str::length($firstParagraph) > $maxLength) {
                $truncated = Str::substr($firstParagraph, 0, $maxLength);
                $cut = Str::beforeLast($truncated, ' ');
                $trimmed = (string) Str::of($cut)->trim()->replaceMatches('/[^\p{L}]+$/u', '');
                $excerpt = $trimmed . '...';
            } else {
                $trimmed = (string) Str::of($firstParagraph)->replaceMatches('/[^\p{L}]+$/u', '');
                $excerpt = $trimmed . '...';
            }
            return Str::length($excerpt) >= 30 ? $excerpt : null;
        };

        $session = $request->getAttribute('session');
        $csrfToken = $session ? $session->token() : '';

        $activeTagName = null;
        if (count($filterSlugs) === 1) {
            foreach ($primaryTags as $tag) {
                $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                if ($slug === $filterSlugs[0]) {
                    $activeTagName = $tag['attributes']['name'] ?? $filterSlugs[0];
                    break;
                }
            }
        }

        return $this->response('custom-frontend::discussions.index', [
            'apiDocument' => $apiDocument,
            'page' => $page,
            'hasNextPage' => $hasNextPage,
            'url' => $this->url,
            'getResource' => $getResource,
            'getDiscussionExcerpt' => $getDiscussionExcerpt,
            'primaryTags' => $primaryTags,
            'subscribedTagSlugs' => $subscribedTagSlugs,
            'filterSlugs' => $filterSlugs,
            'activeTagName' => $activeTagName,
            'subscriptionApiAvailable' => $subscriptionApiAvailable,
            'csrfToken' => $csrfToken,
            'translator' => $this->translator,
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

    public function updateTagSubscriptions(Request $request): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $tagSlugs = $data['tag_slugs'] ?? $data['tag_slugs'] ?? [];
        if (! is_array($tagSlugs)) {
            $tagSlugs = $tagSlugs !== '' ? [trim((string) $tagSlugs)] : [];
        } else {
            $tagSlugs = array_values(array_filter(array_map('trim', $tagSlugs), fn ($s) => $s !== ''));
        }

        $response = $this->api
            ->withParentRequest($request)
            ->withBody(['slugs' => $tagSlugs])
            ->put('/tag-subscriptions');

        $indexUrl = $this->url->to('forum')->route('custom-frontend.index');
        if ($response->getStatusCode() !== 200 && $tagSlugs !== []) {
            $indexUrl .= '?tag=' . implode(',', array_map('urlencode', $tagSlugs));
        }

        return new RedirectResponse($indexUrl);
    }

    public function create(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $csrfToken = $session ? $session->token() : '';

        $tagsForSelect = $this->getTagsForDiscussionCreate($request);

        $html = $this->view->make('custom-frontend::discussions.create', [
            'url' => $this->url,
            'csrfToken' => $csrfToken,
            'tagsForSelect' => $tagsForSelect,
            'translator' => $this->translator,
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

        $data = [
            'type' => 'discussions',
            'attributes' => [
                'title' => $title,
                'content' => $validated['content'],
            ],
        ];

        if (!empty($validated['tag_ids'])) {
            $data['relationships'] = [
                'tags' => [
                    'data' => array_map(
                        fn ($id) => ['type' => 'tags', 'id' => (string) $id],
                        $validated['tag_ids']
                    ),
                ],
            ];
        }

        $body = ['data' => $data];

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

    /**
     * Tags the user can select when starting a discussion (canStartDiscussion).
     * Returns primary tags first, then children, with canStartDiscussion true.
     *
     * @return list<array{id: string, type: string, attributes: array}>
     */
    protected function getTagsForDiscussionCreate(Request $request): array
    {
        $response = $this->api
            ->withParentRequest($request)
            ->get('/tags');

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $document = json_decode($response->getBody()->getContents(), true);
        $data = $document['data'] ?? [];

        $canStart = array_filter($data, function ($resource) {
            $attrs = $resource['attributes'] ?? [];
            return !empty($attrs['canStartDiscussion'] ?? false);
        });

        // Sort: primary first (by position), then children under their parent
        usort($canStart, function ($a, $b) {
            $aAttrs = $a['attributes'] ?? [];
            $bAttrs = $b['attributes'] ?? [];
            $aParent = $aAttrs['isChild'] ?? false;
            $bParent = $bAttrs['isChild'] ?? false;
            if ($aParent !== $bParent) {
                return $aParent ? 1 : -1;
            }
            $aPos = $aAttrs['position'] ?? 0;
            $bPos = $bAttrs['position'] ?? 0;
            return ($aPos <=> $bPos) ?: (($a['id'] ?? '') <=> ($b['id'] ?? ''));
        });

        return array_values($canStart);
    }

    /**
     * Primary tags (top-level, no parent) for the feed filter checkboxes.
     *
     * @return list<object{id: string, type: string, attributes: object}>
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

    /**
     * Tag slugs the current user is subscribed to (from tag-subscriptions API).
     * Returns [] if API is not available or user is guest.
     *
     * @return string[]
     */
    protected function getSubscribedTagSlugs(Request $request): array
    {
        $response = $this->api->withParentRequest($request)->get('/tag-subscriptions');

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $body = json_decode($response->getBody()->getContents(), true);

        return is_array($body['slugs'] ?? null) ? $body['slugs'] : [];
    }

    protected function isTagSubscriptionsApiAvailable(Request $request): bool
    {
        $response = $this->api->withParentRequest($request)->get('/tag-subscriptions');

        return $response->getStatusCode() === 200;
    }

    /**
     * Parse ?tag=slug or ?tag=slug1,slug2 into array of slugs.
     *
     * @return string[]
     */
    protected function parseTagFilter(?string $tagParam): array
    {
        if ($tagParam === null || $tagParam === '') {
            return [];
        }

        $slugs = array_map('trim', explode(',', $tagParam));

        return array_values(array_filter($slugs, fn ($s) => $s !== ''));
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
