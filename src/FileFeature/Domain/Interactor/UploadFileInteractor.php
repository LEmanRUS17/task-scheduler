<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Interactor;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;

final class UploadFileInteractor
{
    public function __construct(
        private readonly FileRepositoryInterface $files,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function upload(
        string $entityClass,
        string $entityId,
        FilePurpose $purpose,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): StoredFile {
        // Avatars are single-per-entity: drop the previous one before storing the new file.
        if ($purpose === FilePurpose::Avatar) {
            $existing = $this->files->findAvatar($entityClass, $entityId);

            if ($existing !== null) {
                $this->storage->delete($existing->storagePath());
                $this->files->delete($existing);
            }
        }

        $id = $this->generateUuid();
        $now = new \DateTimeImmutable();
        $relativePath = $this->buildRelativePath($purpose, $id, $originalName, $now);

        $this->storage->store($tmpPath, $relativePath);

        $file = StoredFile::create(
            $id,
            $entityClass,
            $entityId,
            $purpose,
            $originalName,
            $relativePath,
            $mimeType,
            $size,
            $uploadedBy,
            $now,
        );

        $this->files->save($file);

        return $file;
    }

    private function buildRelativePath(
        FilePurpose $purpose,
        string $id,
        string $originalName,
        \DateTimeImmutable $now,
    ): string {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $suffix = $extension !== '' ? '.' . $extension : '';

        return sprintf('%s/%s/%s%s', $purpose->value, $now->format('Y/m'), $id, $suffix);
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
