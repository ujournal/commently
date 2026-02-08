<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Extend;
use Flarum\Likes\Api\LoadLikesRelationship;
use Flarum\Http\Middleware\CheckCsrfToken;
use App\Access\AllowLikePolicy;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Illuminate\Support\Collection;

return [
    // Disable CSRF checks for API requests (useful for API-only clients)
    (new Extend\Middleware('api'))
        ->remove(CheckCsrfToken::class),

    // Use global discussion.likePosts for liking (no per-tag permission required)
    (new Extend\Policy())
        ->modelPolicy(Post::class, AllowLikePolicy::class)
        ->modelPolicy(CommentPost::class, AllowLikePolicy::class),
    (new FoF\Upload\Extend\Adapters())
        ->force('aws-s3'),

    // Expose canLike and likesCount on firstPost/lastPost (they use BasicPostSerializer)
    (new Extend\ApiSerializer(BasicPostSerializer::class))
        ->attributes(function ($serializer, $post) {
            return [
                'canLike' => (bool) $serializer->getActor()->can('like', $post),
                'likesCount' => (int) ($post->getAttribute('likes_count') ?? 0),
            ];
        }),

    // Allow firstPost.likes on discussions list and load like count for firstPost
    (new Extend\ApiController(ListDiscussionsController::class))
        ->addInclude('firstPost.likes')
        ->loadWhere('firstPost.likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization(function ($controller, $data) {
            if ($data instanceof Collection) {
                foreach ($data->pluck('firstPost')->filter() as $post) {
                    $post->loadCount('likes');
                }
            }
        }),
];
