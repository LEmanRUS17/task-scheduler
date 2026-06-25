<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Domain\ValueObject;

use App\FileFeatureApi\Contract\ImageSize;
use PHPUnit\Framework\TestCase;

final class ImageSizeTest extends TestCase
{
    public function testDefaultIsLargest(): void
    {
        self::assertSame(ImageSize::Large, ImageSize::default());
    }

    public function testFromNameMapsKnownNames(): void
    {
        self::assertSame(ImageSize::Small, ImageSize::fromName('small'));
        self::assertSame(ImageSize::Medium, ImageSize::fromName('MEDIUM'));
        self::assertSame(ImageSize::Large, ImageSize::fromName('large'));
    }

    public function testFromNameFallsBackToDefault(): void
    {
        self::assertSame(ImageSize::default(), ImageSize::fromName(null));
        self::assertSame(ImageSize::default(), ImageSize::fromName('unknown'));
    }

    public function testFileNameUsesPixelSize(): void
    {
        self::assertSame('72.webp', ImageSize::Small->fileName());
        self::assertSame('384.webp', ImageSize::Large->fileName());
        self::assertSame(144, ImageSize::Medium->pixels());
    }
}
