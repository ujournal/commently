<?php

namespace UJournal\SortByLikes;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Extend;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Deleting;
use Flarum\Post\Post;
use UJournal\SortByLikes\Access\AllowGlobalLikePostPolicy;
use UJournal\SortByLikes\Access\AllowGlobalLikePostsPolicy;
use UJournal\SortByLikes\Filter\ApplyHotSortMutator;

return [
    (new Extend\Policy())
        ->modelPolicy(Discussion::class, AllowGlobalLikePostsPolicy::class)
        ->modelPolicy(Post::class, AllowGlobalLikePostPolicy::class)
        ->modelPolicy(CommentPost::class, AllowGlobalLikePostPolicy::class),

    (new Extend\Event())
        ->listen(PostWasLiked::class, Listeners\UpdateDiscussionLikeCount::class)
        ->listen(PostWasUnliked::class, Listeners\UpdateDiscussionLikeCount::class)
        ->listen(Deleting::class, Listeners\UpdateDiscussionLikeCountWhenPostDeleted::class),

    (new Extend\ApiController(ListDiscussionsController::class))
        ->addSortField('likeCount')
        ->addSortField('hot'),

    (new Extend\Filter(DiscussionFilterer::class))
        ->addFilterMutator(ApplyHotSortMutator::class),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attributes(function ($serializer, $discussion) {
            return [
                'likeCount' => (int) ($discussion->like_count ?? 0),
            ];
        }),
];
