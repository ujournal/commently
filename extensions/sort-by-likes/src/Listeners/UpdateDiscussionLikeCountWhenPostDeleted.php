<?php

namespace Commently\SortByLikes\Listeners;

use Flarum\Discussion\Discussion;
use Flarum\Post\Event\Deleting;

class UpdateDiscussionLikeCountWhenPostDeleted
{
    public function handle(Deleting $event): void
    {
        $post = $event->post;
        // Only adjust discussion like_count when the deleted post is the first post
        if ((int) ($post->getAttribute('number') ?? $post->number ?? 0) !== 1) {
            return;
        }

        $count = $post->likes()->count();
        if ($count > 0) {
            Discussion::where('id', $post->discussion_id)->decrement('like_count', $count);
        }
    }
}
