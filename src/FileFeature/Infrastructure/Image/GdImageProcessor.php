<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Image;

use App\FileFeature\Domain\Port\ImageProcessorInterface;
use App\FileFeatureApi\Contract\ImageSize;

/**
 * Base processor: square center-crop + resize to the target size.
 *
 * Geometry only — it emits a lossless PNG so an encoding decorator can take
 * over the final format/optimization without compounding lossy artifacts.
 */
final class GdImageProcessor implements ImageProcessorInterface
{
    public function process(string $source, ImageSize $size): string
    {
        $src = @imagecreatefromstring($source);

        if ($src === false) {
            throw new \RuntimeException('Unsupported or corrupted image.');
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $side = min($width, $height);
        $srcX = intdiv($width - $side, 2);
        $srcY = intdiv($height - $side, 2);
        $px = $size->pixels();

        $dst = imagecreatetruecolor($px, $px);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $px, $px, $side, $side);

        ob_start();
        imagepng($dst);
        $out = (string) ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $out;
    }
}
