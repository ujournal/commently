<?php

namespace App\Extend;

use App\Formatter\ParseBeforeRenderFormatter;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Wraps the Flarum formatter so raw BBCode is parsed before render (fixes posts with raw content).
 */
class WrapFormatterWithParseBeforeRender implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.formatter', function ($formatter) {
            return new ParseBeforeRenderFormatter($formatter);
        });
    }
}
