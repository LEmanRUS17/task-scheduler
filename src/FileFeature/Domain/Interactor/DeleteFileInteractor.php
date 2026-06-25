<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Interactor;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use App\FileFeatureApi\Contract\ImageSize;

final class DeleteFileInteractor
{
    public function __construct(
        private readonly FileRepositoryInterface $files,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function delete(StoredFile $file): void
    {
        if ($file->purpose() === FilePurpose::Avatar) {
            // Avatars are stored as a directory of rendered size variants.
            foreach (ImageSize::cases() as $size) {
                $this->storage->delete($file->storagePath() . '/' . $size->fileName());
            }
        } else {
            $this->storage->delete($file->storagePath());
        }

        $this->files->delete($file);
    }
}
