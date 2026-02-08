<?php

namespace Tests\Unit\UploadDimensions;

use FoF\Upload\File;
use FoF\Upload\Events\File\WillBeUploaded;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Commently\UploadDimensions\Listeners\AddFileDimensions;

class AddFileDimensionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_skips_non_image_types(): void
    {
        $file = Mockery::mock(File::class)->makePartial();
        $file->shouldReceive('setAttribute')->andReturnSelf();
        $file->type = 'application/pdf';

        $upload = Mockery::mock(UploadedFile::class);
        $upload->shouldNotReceive('getRealPath');

        $event = Mockery::mock(WillBeUploaded::class);
        $event->file = $file;
        $event->uploadedFile = $upload;

        $listener = new AddFileDimensions();
        $listener->handle($event);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_skips_when_file_type_is_null(): void
    {
        $file = Mockery::mock(File::class)->makePartial();
        $file->shouldReceive('setAttribute')->andReturnSelf();
        $file->type = null;
        $upload = Mockery::mock(UploadedFile::class);
        $upload->shouldNotReceive('getRealPath');

        $event = Mockery::mock(WillBeUploaded::class);
        $event->file = $file;
        $event->uploadedFile = $upload;

        $listener = new AddFileDimensions();
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
