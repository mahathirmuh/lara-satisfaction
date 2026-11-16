<?php

namespace Tests\Unit;

use App\Helpers\FileApps;
use PHPUnit\Framework\TestCase;

class FileAppsThumbnailTest extends TestCase
{
    public function test_a_portrait_photo_becomes_a_square_webp_thumbnail(): void
    {
        $image = imagecreatetruecolor(300, 400);
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();

        $thumbnail = FileApps::thumbnail($jpeg, 160);

        $this->assertNotNull($thumbnail);
        $this->assertSame([160, 160, IMAGETYPE_WEBP], array_slice((array) getimagesizefromstring($thumbnail), 0, 3));
    }

    public function test_bytes_that_are_not_an_image_give_no_thumbnail(): void
    {
        $this->assertNull(FileApps::thumbnail('not an image', 160));
    }
}
