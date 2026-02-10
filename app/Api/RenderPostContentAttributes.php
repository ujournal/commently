<?php

namespace App\Api;

use Flarum\Formatter\Formatter;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Ensures post content is rendered even when stored as raw BBCode (parse-then-render fallback).
 * Prevents infinite loading and render_failed_message when content was saved unparsed.
 */
class RenderPostContentAttributes
{
    public function __construct(
        private Formatter $formatter,
        private TranslatorInterface $translator,
        private LogReporter $log
    ) {
    }

    public function __invoke($serializer, Post $post, array $attributes): array
    {
        if (!($post instanceof CommentPost)) {
            return [];
        }

        $request = $serializer->getRequest();
        $content = $post->content;

        // If content looks like raw BBCode (not XML), parse then render first to avoid render failure
        $trimmed = is_string($content) ? trim($content) : '';
        $looksRaw = $trimmed !== '' && ($trimmed[0] ?? '') !== '<' && str_contains($trimmed, '[');

        if ($looksRaw) {
            try {
                $actor = $serializer->getActor();
                $xml = $this->formatter->parse($content, $post, $actor);
                $html = $this->formatter->render($xml, $post, $request);
                return ['contentHtml' => $html, 'renderFailed' => false];
            } catch (Throwable $e) {
                $this->log->report($e);
                return [
                    'contentHtml' => $this->translator->trans('core.lib.error.render_failed_message'),
                    'renderFailed' => true,
                ];
            }
        }

        try {
            $html = $post->formatContent($request);
            return ['contentHtml' => $html, 'renderFailed' => false];
        } catch (Throwable $e) {
            $this->log->report($e);
            return [
                'contentHtml' => $this->translator->trans('core.lib.error.render_failed_message'),
                'renderFailed' => true,
            ];
        }
    }
}
