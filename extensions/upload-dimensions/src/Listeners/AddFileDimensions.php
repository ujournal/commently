<?php

namespace Commently\UploadDimensions\Listeners;

use FoF\Upload\Events\File\WillBeUploaded;
use Intervention\Image\ImageManager;

class AddFileDimensions
{
    public function handle(WillBeUploaded $event): void
    {
        $file = $event->file;
        $upload = $event->uploadedFile;

        if (!str_starts_with($file->type ?? '', 'image/')) {
            return;
        }

        $path = $upload->getRealPath();
        if (!$path || !is_readable($path)) {
            return;
        }

        try {
            $manager = new ImageManager(['driver' => 'gd']);
            $image = $manager->make($path);
            $file->width = $image->width();
            $file->height = $image->height();
        } catch (\Throwable $e) {
            // Non-image or unreadable - ignore
        }
    }
}
