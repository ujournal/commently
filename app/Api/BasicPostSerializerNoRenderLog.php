<?php

namespace App\Api;

use Exception;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use InvalidArgumentException;

/**
 * Same as BasicPostSerializer but does not log render failures to the log file.
 */
class BasicPostSerializerNoRenderLog extends BasicPostSerializer
{
    protected function getDefaultAttributes($post)
    {
        if (! ($post instanceof Post)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Post::class
            );
        }

        $attributes = [
            'number' => (int) $post->number,
            'createdAt' => $this->formatDate($post->created_at),
            'contentType' => $post->type
        ];

        if ($post instanceof CommentPost) {
            try {
                $attributes['contentHtml'] = $post->formatContent($this->request);
                $attributes['renderFailed'] = false;
            } catch (Exception $e) {
                $attributes['contentHtml'] = $this->translator->trans('core.lib.error.render_failed_message');
                // Intentionally do not log render errors to avoid log spam
                $attributes['renderFailed'] = true;
            }
        } else {
            $attributes['content'] = $post->content;
        }

        return $attributes;
    }
}
