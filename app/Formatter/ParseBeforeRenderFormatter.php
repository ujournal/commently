<?php

namespace App\Formatter;

use Flarum\Formatter\Formatter;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

/**
 * Wraps the Flarum Formatter and parses raw BBCode before rendering when needed.
 * Fixes posts that have raw content stored so they display instead of "render_failed_message".
 */
class ParseBeforeRenderFormatter extends Formatter
{
    private Formatter $inner;

    public function __construct(Formatter $inner)
    {
        $this->inner = $inner;
        $r = new ReflectionClass($inner);
        $cache = $r->getProperty('cache');
        $cache->setAccessible(true);
        $cacheDir = $r->getProperty('cacheDir');
        $cacheDir->setAccessible(true);
        parent::__construct($cache->getValue($inner), $cacheDir->getValue($inner));
    }

    /**
     * {@inheritdoc}
     * When content looks like raw BBCode (not XML), parse it first then render.
     */
    public function render($xml, $context = null, ServerRequestInterface $request = null)
    {
        $trimmed = is_string($xml) ? trim($xml) : '';
        $looksRaw = $trimmed !== '' && ($trimmed[0] ?? '') !== '<' && str_contains($trimmed, '[');

        if ($looksRaw) {
            $actor = null;
            if ($request !== null) {
                try {
                    $actor = RequestUtil::getActor($request);
                } catch (\Throwable $e) {
                    // guest or no actor
                }
            }
            $xml = $this->inner->parse($xml, $context, $actor);
        }

        return $this->inner->render($xml, $context, $request);
    }

    public function parse($text, $context = null, $user = null)
    {
        return $this->inner->parse($text, $context, $user);
    }

    public function unparse($xml, $context = null)
    {
        return $this->inner->unparse($xml, $context);
    }

    public function flush()
    {
        return $this->inner->flush();
    }

    public function getJs()
    {
        return $this->inner->getJs();
    }

    public function addConfigurationCallback($callback)
    {
        return $this->inner->addConfigurationCallback($callback);
    }

    public function addParsingCallback($callback)
    {
        return $this->inner->addParsingCallback($callback);
    }

    public function addUnparsingCallback($callback)
    {
        return $this->inner->addUnparsingCallback($callback);
    }

    public function addRenderingCallback($callback)
    {
        return $this->inner->addRenderingCallback($callback);
    }
}
