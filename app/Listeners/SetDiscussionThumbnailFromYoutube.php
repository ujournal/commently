<?php

namespace App\Listeners;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use FoF\Upload\Events\File\WasSaved;
use FoF\Upload\Events\File\WillBeSaved;
use FoF\Upload\Events\File\WillBeUploaded;
use FoF\Upload\File;
use FoF\Upload\Helpers\Util;
use FoF\Upload\Repositories\FileRepository;
use App\Api\SetYoutubeDiscussionThumbnailAttribute;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * When the first post of a discussion is saved or revised we look at the raw content,
 * check if it has a link to a YouTube video. If it has, we take the video cover (thumbnail),
 * upload it via the S3 driver (FoF Upload), and set it as the discussion thumbnail (FoF cache).
 */
class SetDiscussionThumbnailFromYoutube
{
    /** Match YouTube video ID in common URL forms (www optional, query params, embed, short link). */
    private const YOUTUBE_ID_REGEX = '(?:(?:www\.)?youtube\.com\/(?:watch\?(?:[^&]*&)*v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})';

    public function __construct(
        protected Cache $cache,
        protected FileRepository $files,
        protected Util $util,
        protected Dispatcher $events
    ) {
    }

    public function handle(Posted|Revised $event): void
    {
        $post = $event->post;

        if (! $post instanceof CommentPost) {
            return;
        }
        // Only run for the discussion's first post (number can be 1, or match first_post_id)
        $isFirstPost = (int) $post->number === 1;
        if (! $isFirstPost && $post->discussion_id) {
            $discussion = $post->relationLoaded('discussion') ? $post->discussion : Discussion::find($post->discussion_id);
            $isFirstPost = $discussion && (int) $discussion->first_post_id === (int) $post->id;
        }
        if (! $isFirstPost) {
            return;
        }

        $key = "fof-discussion-thumbnail.discussion.{$post->id}";

        // Check if first post content already has an image (discussion-thumbnail will use it)
        $contentHtml = '';
        try {
            $contentHtml = $post->formatContent() ?? '';
        } catch (\Throwable $e) {
            // Continue without HTML check (e.g. render failed)
        }
        if ($contentHtml !== '' && preg_match('/<img.+?src=[\'"].+?[\'"].*?>/i', $contentHtml)) {
            $this->cache->forget($key);
            $this->cache->forget(SetYoutubeDiscussionThumbnailAttribute::CACHE_KEY_PREFIX . $post->id);
            return;
        }

        // Check raw content for YouTube link (stored content first, then unparsed)
        $videoId = $this->extractYoutubeVideoIdFromRawContent($post);
        if (! $videoId) {
            $this->cache->forget($key);
            $this->cache->forget(SetYoutubeDiscussionThumbnailAttribute::CACHE_KEY_PREFIX . $post->id);
            return;
        }

        // Download video cover (maxresdefault = 16:9, no letterboxing), resize to 640px width, upload via S3
        $tempPath = $this->downloadYoutubeCoverToTemp($videoId);
        if (! $tempPath) {
            $this->cache->forget($key);
            $this->cache->forget(SetYoutubeDiscussionThumbnailAttribute::CACHE_KEY_PREFIX . $post->id);
            return;
        }

        $this->resizeToMaxWidth($tempPath, 640);

        try {
            $this->uploadCoverViaS3AndSetCache($post, $videoId, $tempPath, $key);
        } catch (\Throwable $e) {
            $this->cache->forget($key);
            $this->cache->forget(SetYoutubeDiscussionThumbnailAttribute::CACHE_KEY_PREFIX . $post->id);
        } finally {
            if (isset($tempPath) && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /** Set cache exactly as FoF Discussion Thumbnail expects (key + url/date structure). */
    private function setFofThumbnailCache(string $key, string $url, CommentPost $post): void
    {
        $this->cache->forever($key, [
            'url'  => $url,
            'date' => $post->edited_at ?? $post->created_at,
        ]);
    }

    /** Upload the cover image via S3 driver (FoF Upload) and set FoF thumbnail cache. */
    private function uploadCoverViaS3AndSetCache(CommentPost $post, string $videoId, string $tempPath, string $cacheKey): void
    {
        try {
        $upload = new SymfonyUploadedFile(
            $tempPath,
            'youtube-cover-'.$videoId.'.jpg',
            'image/jpeg',
            UPLOAD_ERR_OK,
            true
        );

        $mime = $this->files->determineMime($upload);
        if (! $mime || ! preg_match('/^image\//', $mime)) {
            return;
        }

        $actor = $post->user;
        $file = $this->files->createFileFromUpload(
            $upload,
            $actor,
            $mime,
            true,  // hideFromMediaManager
            false  // not shared
        );

        // Use S3 driver (project forces aws-s3 via extend.php)
        $adapter = $this->util->getAdapter('aws-s3');
        if (! $adapter) {
            return;
        }

            $this->events->dispatch(new WillBeUploaded($actor, $file, $upload, $mime));

            $contents = $this->files->readUpload($upload, $adapter);
            $result = $adapter->upload($file, $upload, $contents);
            if (! $result instanceof File) {
                return;
            }

            $file = $result;
            $file->upload_method = $this->util->setMethod($adapter);
            $mimeConfig = $this->util->getMimeConfiguration($mime);
            $template = $this->util->getTemplate(Arr::get($mimeConfig, 'template', 'image-preview'));
            if ($template !== null) {
                $file->tag = $template;
            }

            $this->events->dispatch(new WillBeSaved($actor, $file, $upload, $mime));
            if ($file->isDirty() || ! $file->exists) {
                $file->save();
            }
            $this->events->dispatch(new WasSaved($actor, $file, $upload, $mime));

            $file->posts()->syncWithoutDetaching([$post->id]);

            // Set discussion thumbnail to the S3 URL (FoF Discussion Thumbnail cache)
            $this->setFofThumbnailCache($cacheKey, $file->url, $post);
            // Also set our own cache so API attribute can use S3 URL when FoF overwrites with null (no <img> in HTML)
            $this->cache->forever(SetYoutubeDiscussionThumbnailAttribute::CACHE_KEY_PREFIX . $post->id, $file->url);
        } finally {
            if (isset($tempPath) && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Check raw content for a YouTube video link. Uses stored raw content first, then unparsed.
     */
    private function extractYoutubeVideoIdFromRawContent(CommentPost $post): ?string
    {
        // 1. Raw stored content (as saved in DB – parsed XML or raw string)
        $rawStored = (string) ($post->getRawOriginal('content') ?? '');
        $id = $this->extractYoutubeVideoId(html_entity_decode($rawStored, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($id !== null) {
            return $id;
        }
        // 2. Unparsed content (what the user typed – markdown/BBCode)
        $unparsed = (string) $post->content;
        $id = $this->extractYoutubeVideoId(html_entity_decode($unparsed, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($id !== null) {
            return $id;
        }
        return null;
    }

    private function extractYoutubeVideoId(string $content): ?string
    {
        if (preg_match('/' . self::YOUTUBE_ID_REGEX . '/', $content, $m)) {
            return $m[1];
        }
        return null;
    }

    /** maxresdefault = 1280×720 (16:9, no letterboxing). Fallback when not available: hqdefault. */
    private const YOUTUBE_COVER_SUFFIX_PRIMARY = 'maxresdefault';
    private const YOUTUBE_COVER_SUFFIX_FALLBACK = 'hqdefault';

    private function getYoutubeCoverUrl(string $videoId, bool $usePrimary = true): string
    {
        $suffix = $usePrimary ? self::YOUTUBE_COVER_SUFFIX_PRIMARY : self::YOUTUBE_COVER_SUFFIX_FALLBACK;
        return "https://img.youtube.com/vi/{$videoId}/{$suffix}.jpg";
    }

    /** YouTube serves a 120×90 letterboxed placeholder when a size is not available. */
    private const YOUTUBE_PLACEHOLDER_WIDTH = 120;
    private const YOUTUBE_PLACEHOLDER_HEIGHT = 90;

    /** Download YouTube video cover to a temp file. Prefers maxresdefault (16:9); if placeholder, falls back to hqdefault. */
    private function downloadYoutubeCoverToTemp(string $videoId): ?string
    {
        $tempPath = @tempnam(sys_get_temp_dir(), 'commently-yt-thumb.');
        if (! $tempPath) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => 1,
                'user_agent' => 'Commently/1.0 (YouTube thumbnail)',
            ],
        ]);

        $url = $this->getYoutubeCoverUrl($videoId, true);
        $data = @file_get_contents($url, false, $context);
        if ($data === false || strlen($data) < 100) {
            $url = $this->getYoutubeCoverUrl($videoId, false);
            $data = @file_get_contents($url, false, $context);
        } elseif (@file_put_contents($tempPath, $data) !== false) {
            $info = @getimagesize($tempPath);
            if (
                $info !== false
                && (int) ($info[0] ?? 0) === self::YOUTUBE_PLACEHOLDER_WIDTH
                && (int) ($info[1] ?? 0) === self::YOUTUBE_PLACEHOLDER_HEIGHT
            ) {
                @unlink($tempPath);
                $url = $this->getYoutubeCoverUrl($videoId, false);
                $data = @file_get_contents($url, false, $context);
            }
        }

        if ($data === false || strlen($data) < 100) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            return null;
        }
        if (@file_put_contents($tempPath, $data) === false) {
            @unlink($tempPath);
            return null;
        }
        return $tempPath;
    }

    /** Resize image in place to max width (keeps aspect ratio). Uses GD; no-op if width already <= maxWidth or GD fails. */
    private function resizeToMaxWidth(string $path, int $maxWidth): void
    {
        $info = @getimagesize($path);
        if ($info === false || ($info[0] ?? 0) <= $maxWidth) {
            return;
        }
        $w = (int) $info[0];
        $h = (int) $info[1];
        $newW = $maxWidth;
        $newH = (int) round($h * ($maxWidth / $w));

        $src = @imagecreatefromstring((string) file_get_contents($path));
        if ($src === false) {
            return;
        }
        $dst = @imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            return;
        }
        if (! @imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h)) {
            imagedestroy($src);
            imagedestroy($dst);
            return;
        }
        imagedestroy($src);
        if (@imagejpeg($dst, $path, 88)) {
            // nop
        }
        imagedestroy($dst);
    }
}
