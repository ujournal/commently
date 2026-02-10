<?php

namespace App\Extend;

use App\Api\BasicPostSerializerNoRenderLog;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Use a post serializer that does not log content render errors to the log file.
 */
class UseBasicPostSerializerNoRenderLog implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        $container->bind(BasicPostSerializer::class, BasicPostSerializerNoRenderLog::class);
    }
}
