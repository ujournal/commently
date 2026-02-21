<?php

namespace App\Api;

use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;

/**
 * Returns width/height for the discussion's customThumbnail when available:
 * - YouTube thumbnail (direct URL): 1280×720 (maxresdefault); uploaded thumbnails use file dimensions (resized to 640×360)
 * - First post image from upload (upload-dimensions): matching or first image file's width/height
 */
class GetDiscussionThumbnailDimensions
{
    /**
     * @return array{width: int|null, height: int|null}
     */
    public static function get(Discussion $discussion, ?string $thumbnailUrl): array
    {
        if ($thumbnailUrl === null || $thumbnailUrl === '') {
            return ['width' => null, 'height' => null];
        }

        if (str_contains($thumbnailUrl, 'img.youtube.com')) {
            return ['width' => 1280, 'height' => 720];
        }

        if (! $discussion->relationLoaded('firstPost')) {
            $discussion->load('firstPost');
        }

        $post = $discussion->firstPost;
        if (! $post instanceof CommentPost || ! $post->exists) {
            return ['width' => null, 'height' => null];
        }

        if (! $post->relationLoaded('uploadFiles')) {
            $post->load('uploadFiles');
        }

        $imageFiles = $post->uploadFiles->filter(
            fn ($file) => str_starts_with((string) ($file->type ?? ''), 'image/')
        );

        // Prefer the file whose URL matches the thumbnail (e.g. same upload used in post content)
        $thumbnailUrlNormalized = rtrim($thumbnailUrl, '/');
        $matching = $imageFiles->first(function ($file) use ($thumbnailUrlNormalized) {
            $fileUrl = isset($file->url) ? rtrim((string) $file->url, '/') : '';
            return $fileUrl !== '' && ($fileUrl === $thumbnailUrlNormalized || str_contains($thumbnailUrlNormalized, $fileUrl) || str_contains($fileUrl, $thumbnailUrlNormalized));
        });
        $file = $matching ?? $imageFiles->first();

        if ($file === null) {
            return ['width' => null, 'height' => null];
        }

        $w = isset($file->width) && (int) $file->width > 0 ? (int) $file->width : null;
        $h = isset($file->height) && (int) $file->height > 0 ? (int) $file->height : null;

        return [
            'width' => $w,
            'height' => $h,
        ];
    }
}
