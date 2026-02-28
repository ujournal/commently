<?php

namespace Commently\UploadDimensions\Console;

use Flarum\Console\AbstractCommand;
use FoF\Upload\File;

class BackfillDimensionsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('upload-dimensions:backfill')
            ->setDescription('Backfill width/height for existing image uploads that have none.');
    }

    protected function fire(): int
    {
        $query = File::query()
            ->where('type', 'like', 'image/%')
            ->where(function ($q) {
                $q->whereNull('width')->orWhereNull('height');
            });

        $total = $query->count();
        if ($total === 0) {
            $this->info('No image files missing dimensions.');
            return 0;
        }

        $this->info("Found {$total} image(s) missing dimensions.");

        $updated = 0;
        foreach ($query->cursor() as $file) {
            /** @var File $file */
            $url = $file->url ?? '';
            if ($url === '') {
                continue;
            }

            $size = @getimagesize($url);
            if ($size === false || ! isset($size[0], $size[1]) || $size[0] < 1 || $size[1] < 1) {
                continue;
            }

            $file->width = (int) $size[0];
            $file->height = (int) $size[1];
            $file->save();
            $updated++;
        }

        $this->info("Updated {$updated} file(s).");
        return 0;
    }
}
