<?php

namespace UJournal\SortByLikes\Listeners;

use Flarum\Discussion\Discussion;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;

class UpdateDiscussionLikeCount
{
    public function handle(object $event): void
    {
        $post = $event->post;
        $discussionId = $post->getAttribute('discussion_id') ?? $post->discussion_id ?? null;

        if (! $discussionId) {
            return;
        }

        if ($event instanceof PostWasLiked) {
            Discussion::where('id', $discussionId)->increment('like_count');
        } elseif ($event instanceof PostWasUnliked) {
            Discussion::where('id', $discussionId)->decrement('like_count');
        }
    }
}
