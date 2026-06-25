<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Port;

use App\FileFeatureApi\Contract\ImageSize;

/**
 * Single entry point that turns a source image plus a target size into the
 * rendered image binary. Implementations are composed as a decorator chain so
 * each stage (geometry, encoding, optimization) processes the previous output.
 */
interface ImageProcessorInterface
{
    /**
     * @param string $source raw bytes of the source image
     * @return string raw bytes of the processed image
     *
     * @throws \RuntimeException when the source cannot be decoded
     */
    public function process(string $source, ImageSize $size): string;
}
