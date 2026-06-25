<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Infrastructure;

use App\FileFeature\Infrastructure\Image\GdImageProcessor;
use App\FileFeature\Infrastructure\Image\WebpImageProcessor;
use App\FileFeatureApi\Contract\ImageSize;
use PHPUnit\Framework\TestCase;

final class ImageProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            self::markTestSkipped('GD extension is not available.');
        }

        if (!function_exists('imagewebp') || empty(gd_info()['WebP Support'])) {
            self::markTestSkipped('GD WebP support is not available.');
        }
    }

    public function testDecoratedProcessorProducesSquareWebpOfRequestedSize(): void
    {
        $processor = new WebpImageProcessor(new GdImageProcessor(), quality: 80);

        $webp = $processor->process($this->sampleImage(200, 120), ImageSize::Medium);

        // Output is decodable and square at the requested pixel size.
        $info = getimagesizefromstring($webp);
        self::assertNotFalse($info);
        self::assertSame(ImageSize::Medium->pixels(), $info[0]);
        self::assertSame(ImageSize::Medium->pixels(), $info[1]);
        self::assertSame('image/webp', $info['mime']);
    }

    public function testRejectsNonImageInput(): void
    {
        $this->expectException(\RuntimeException::class);

        (new GdImageProcessor())->process('not-an-image', ImageSize::Small);
    }

    private function sampleImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 100, 150, 200));

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
