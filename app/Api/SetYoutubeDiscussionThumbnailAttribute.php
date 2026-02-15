<?php

namespace App\Api;

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Provides customThumbnail for discussions: use fof's value if present (first post image),
 * otherwise use YouTube thumbnail when first post contains a YouTube video.
 */
class SetYoutubeDiscussionThumbnailAttribute
{
    public const CACHE_KEY_PREFIX = 'commently.youtube-thumbnail.post.';

    /** Match YouTube video ID (11 chars) in common URL forms. */
    private const YOUTUBE_ID_REGEX = '(?:(?:www\.)?youtube\.com/(?:watch\?(?:[^&]*&)*v=|embed/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})';
    /** Fallback: video id after youtube / youtu.be in any form. */
    private const YOUTUBE_ID_FALLBACK = '(?:youtube|youtu\.be)[^a-zA-Z0-9_-]*([a-zA-Z0-9_-]{11})';

    public function __construct(
        protected Cache $cache
    ) {
    }

    /**
     * Invoked as single-attribute callback: return customThumbnail URL or keep existing.
     * @param BasicDiscussionSerializer $serializer
     * @param Discussion $discussion
     * @param array $attributes current attributes (includes fof's customThumbnail if set)
     * @return string|null
     */
    public function __invoke($serializer, $discussion, array $attributes)
    {
        if (! $discussion instanceof Discussion) {
            return $attributes['customThumbnail'] ?? null;
        }
        // Keep fof's thumbnail if first post already has an image
        $existing = $attributes['customThumbnail'] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        if (! $discussion->relationLoaded('firstPost')) {
            $discussion->load('firstPost');
        }
        $post = $discussion->firstPost;
        if (! $post instanceof CommentPost || ! $post->id) {
            return null;
        }

        $key = self::CACHE_KEY_PREFIX . $post->id;
        $url = $this->cache->get($key);
        if (is_string($url) && $url !== '') {
            return $url;
        }

        if ($this->firstPostHasImage($post)) {
            return null;
        }
        $videoId = $this->extractYoutubeVideoIdFromPost($post);
        if ($videoId !== null) {
            return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
        }

        return null;
    }

    private function firstPostHasImage(CommentPost $post): bool
    {
        try {
            $html = $post->formatContent();
            return $html !== '' && $html !== null && preg_match('/<img[^>]+src\s*=\s*["\'][^"\']+["\']/i', $html);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function extractYoutubeVideoIdFromPost(CommentPost $post): ?string
    {
        $sources = [
            (string) $post->content,
            (string) ($post->getRawOriginal('content') ?? ''),
        ];
        foreach ($sources as $content) {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('/' . self::YOUTUBE_ID_REGEX . '/', $content, $m)) {
                return $m[1];
            }
            if (preg_match('/' . self::YOUTUBE_ID_FALLBACK . '/', $content, $m)) {
                return $m[1];
            }
        }
        // URL might only appear in rendered HTML (e.g. embed iframe)
        try {
            $html = (string) $post->formatContent();
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('/' . self::YOUTUBE_ID_REGEX . '/', $html, $m)) {
                return $m[1];
            }
            if (preg_match('/' . self::YOUTUBE_ID_FALLBACK . '/', $html, $m)) {
                return $m[1];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }
}
