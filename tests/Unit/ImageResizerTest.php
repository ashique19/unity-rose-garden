<?php

namespace Tests\Unit;

use App\Support\ImageResizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageResizerTest extends TestCase
{
    #[Test]
    public function it_constrains_large_image_to_max_edge(): void
    {
        $dir = sys_get_temp_dir().'/urg-image-resizer-'.uniqid();
        mkdir($dir);
        $source = $dir.'/source.png';

        $img = imagecreatetruecolor(2000, 1000);
        $bg = imagecolorallocate($img, 20, 40, 80);
        imagefilledrectangle($img, 0, 0, 1999, 999, $bg);
        imagepng($img, $source);
        imagedestroy($img);

        $result = ImageResizer::constrain($source, 1200, 80);

        $this->assertSame(1200, $result['width']);
        $this->assertSame(600, $result['height']);
        $this->assertSame('image/jpeg', $result['mime']);
        $this->assertFileExists($result['path']);
        $this->assertStringEndsWith('.jpg', $result['path']);

        @unlink($result['path']);
        @rmdir($dir);
    }
}
