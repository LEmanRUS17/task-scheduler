<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Image;

use App\FileFeature\Domain\Port\ImageProcessorInterface;
use App\FileFeatureApi\Contract\ImageSize;

/**
 * Decorator: takes the image produced by the inner processor and re-encodes it
 * to WebP at a fixed quality (also strips metadata). Wrapping the geometry stage
 * keeps "resize" and "encode/optimize" as separate, composable concerns.
 */
final class WebpImageProcessor implements ImageProcessorInterface
{
    public function __construct(
        private readonly ImageProcessorInterface $inner,
        private readonly int $quality = 82,
    ) {
    }

    public function process(string $source, ImageSize $size): string
    {
        $processed = $this->inner->process($source, $size);

        $image = @imagecreatefromstring($processed);

        if ($image === false) {
            throw new \RuntimeException('Processed image could not be re-encoded to WebP.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, $this->quality);
        $out = (string) ob_get_clean();

        imagedestroy($image);

        return $out;
    }
}
