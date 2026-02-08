<?php

namespace UJournal\UploadDimensions;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Extend;
use Flarum\Post\Post;
use FoF\Upload\Api\Serializers\FileSerializer;
use FoF\Upload\Events\File\WillBeUploaded;
use FoF\Upload\File;

return [
    (new Extend\Event())
        ->listen(WillBeUploaded::class, Listeners\AddFileDimensions::class),

    (new Extend\ApiSerializer(FileSerializer::class))
        ->attributes(function ($serializer, $file) {
            return [
                'width' => $file->width ?? null,
                'height' => $file->height ?? null,
            ];
        }),

    (new Extend\Model(Post::class))
        ->belongsToMany('uploadFiles', File::class, 'fof_upload_file_posts', 'post_id', 'file_id'),

    (new Extend\ApiSerializer(\Flarum\Api\Serializer\BasicPostSerializer::class))
        ->hasMany('uploadFiles', FileSerializer::class),
        
    (new Extend\ApiSerializer(\Flarum\Api\Serializer\PostSerializer::class))
        ->hasMany('uploadFiles', FileSerializer::class),

    (new Extend\ApiController(\Flarum\Api\Controller\ShowDiscussionController::class))
        ->addInclude('posts.uploadFiles'),

    (new Extend\ApiController(\Flarum\Api\Controller\ShowPostController::class))
        ->addInclude('uploadFiles'),

    (new Extend\ApiController(\Flarum\Api\Controller\ListPostsController::class))
        ->addInclude('uploadFiles'),

    (new Extend\ApiController(ListDiscussionsController::class))
        ->addInclude('firstPost.uploadFiles'),
];
