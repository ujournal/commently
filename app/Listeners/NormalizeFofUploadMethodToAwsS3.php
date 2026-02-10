<?php

namespace App\Listeners;

use FoF\Upload\Events\File\WillBeSaved;

/**
 * FoF Upload sets upload_method from the adapter class name (AwsS3 → "awss3").
 * We force adapter "aws-s3", so normalize to that so Manager::instantiate() finds it.
 */
class NormalizeFofUploadMethodToAwsS3
{
    public function __invoke(WillBeSaved $event): void
    {
        if ($event->file->upload_method === 'awss3') {
            $event->file->upload_method = 'aws-s3';
        }
    }
}
