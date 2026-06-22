<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Interactor;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;

final class DeleteFileInteractor
{
    public function __construct(
        private readonly FileRepositoryInterface $files,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function delete(StoredFile $file): void
    {
        $this->storage->delete($file->storagePath());
        $this->files->delete($file);
    }
}
