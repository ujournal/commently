<?php

namespace Commently\CustomFrontend\Controller;

use Carbon\Carbon;
use Flarum\Api\Client as ApiClient;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Renders the list of primary tags as a turbo-frame for the left nav.
 */
class TagController
{
    public function __construct(
        protected ApiClient $api,
        protected ViewFactory $view,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected ConnectionInterface $db
    ) {
    }

    /**
     * Primary tags with hasUnread set (for layout nav and for /tags frame).
     * Dot only when tag has ≥1 unread discussion with activity today; unread = same rule as discussion list.
     * On DB error we show no dots ([]).
     */
    public function getPrimaryTagsWithBadges(Request $request): array
    {
        $primaryTags = $this->getPrimaryTags($request);
        $tagIdsWithUnread = $this->getTagIdsWithUnreadToday($request);

        if ($tagIdsWithUnread === null) {
            $tagIdsWithUnread = [];
        }
        foreach ($primaryTags as &$tag) {
            $id = $tag['id'] ?? null;
            $tag['attributes'] = $tag['attributes'] ?? [];
            $tag['attributes']['hasUnread'] = $id !== null && in_array((int) $id, $tagIdsWithUnread, true);
        }
        unset($tag);

        return $primaryTags;
    }

    public function index(Request $request): ResponseInterface
    {
        $primaryTags = $this->getPrimaryTagsWithBadges($request);

        $html = $this->view->make('custom-frontend::tags.index', [
            'primaryTags' => $primaryTags,
            'url' => $this->url,
            'translator' => $this->translator,
        ])->render();

        return new HtmlResponse($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
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

    /**
     * Tag IDs that have at least one unread discussion with activity today.
     *
     * Unread = EXACT same rule as DiscussionController::getUnreadDiscussionIds (used for the list):
     * - No row in discussion_user for (user_id, discussion_id) = never opened = always unread, OR
     * - Row exists AND discussions.comment_count > discussion_user.last_read_post_number
     *
     * We use discussions.comment_count (not last_post_number) to match the list and the PATCH payload.
     *
     * @return int[]|null Tag IDs to show dot for, or null on DB error (caller uses []).
     */
    protected function getTagIdsWithUnreadToday(Request $request): ?array
    {
        $actor = RequestUtil::getActor($request);
        if ($actor->isGuest()) {
            return [];
        }

        try {
            $userId = (int) $actor->id;
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();

            // Step 1: Unread discussion IDs with activity today. Same formula as getUnreadDiscussionIds.
            $unreadDiscussionIds = $this->db->table('discussions')
                ->leftJoin('discussion_user', function ($j) use ($userId) {
                    $j->on('discussions.id', '=', 'discussion_user.discussion_id')
                        ->where('discussion_user.user_id', '=', $userId);
                })
                ->where('discussions.last_posted_at', '>=', $todayStart)
                ->where(function ($q) {
                    $q->whereNull('discussion_user.user_id') // No row = never opened = always unread
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('discussion_user.user_id')
                                ->whereColumn('discussions.comment_count', '>', 'discussion_user.last_read_post_number');
                        });
                })
                ->pluck('discussions.id')
                ->all();

            $unreadDiscussionIds = array_values(array_unique(array_map('intval', $unreadDiscussionIds)));
            if ($unreadDiscussionIds === []) {
                return [];
            }

            // Step 2: Distinct tag_id from discussion_tag for those discussions only.
            $tagIds = $this->db->table('discussion_tag')
                ->whereIn('discussion_id', $unreadDiscussionIds)
                ->distinct()
                ->pluck('tag_id')
                ->all();

            return array_values(array_map('intval', array_unique($tagIds)));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Tag IDs that have at least one discussion (fallback when unread query fails or returns empty).
     *
     * @return int[]
     */
    protected function getTagIdsWithDiscussions(): array
    {
        try {
            $rows = $this->db->table('discussion_tag')->distinct()->pluck('tag_id');
            return array_values(array_map('intval', $rows->all()));
        } catch (\Throwable $e) {
            return [];
        }
    }
}
