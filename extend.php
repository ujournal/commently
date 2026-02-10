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

    // FoF Upload: use .env for S3 when not set in Flarum Admin (e.g. on shared hosting)
    (new Extend\Settings())
        ->default('fof-upload.awsS3Region', env('FOF_UPLOAD_AWS_S3_REGION', ''))
        ->default('fof-upload.awsS3Key', env('FOF_UPLOAD_AWS_S3_KEY', ''))
        ->default('fof-upload.awsS3Secret', env('FOF_UPLOAD_AWS_S3_SECRET', ''))
        ->default('fof-upload.awsS3Bucket', env('FOF_UPLOAD_AWS_S3_BUCKET', ''))
        ->default('fof-upload.awsS3Endpoint', env('FOF_UPLOAD_AWS_S3_ENDPOINT', ''))
        ->default('fof-upload.awsS3UsePathStyleEndpoint', env('FOF_UPLOAD_AWS_S3_USE_PATH_STYLE_ENDPOINT', false))
        ->default('fof-upload.awsS3Acl', env('FOF_UPLOAD_AWS_S3_ACL', 'public-read'))
        ->default('fof-upload.awsS3CustomUrl', env('FOF_UPLOAD_AWS_S3_CUSTOM_URL', '')),

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
