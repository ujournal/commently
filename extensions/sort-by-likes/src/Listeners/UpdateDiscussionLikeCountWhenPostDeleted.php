<?php

namespace Commently\SortByLikes\Listeners;

use Flarum\Discussion\Discussion;
use Flarum\Post\Event\Deleting;

class UpdateDiscussionLikeCountWhenPostDeleted
{
    public function handle(Deleting $event): void
    {
        $post = $event->post;
        $count = $post->likes()->count();

        if ($count > 0) {
            Discussion::where('id', $post->discussion_id)->decrement('like_count', $count);
        }
    }
}
