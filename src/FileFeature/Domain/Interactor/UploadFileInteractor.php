<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\Interactor;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Port\ImageProcessorInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use App\FileFeatureApi\Contract\ImageSize;

final class UploadFileInteractor
{
    public function __construct(
        private readonly FileRepositoryInterface $files,
        private readonly FileStorageInterface $storage,
        private readonly ImageProcessorInterface $imageProcessor,
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
            $this->removeExistingAvatar($entityClass, $entityId);
        }

        $id = $this->generateUuid();
        $now = new \DateTimeImmutable();

        if ($purpose === FilePurpose::Avatar) {
            $storagePath = $this->storeAvatarVariants($tmpPath, $id);
            $storedMimeType = 'image/webp';
        } else {
            $storagePath = $this->buildRelativePath($purpose, $id, $originalName, $now);
            $this->storage->store($tmpPath, $storagePath);
            $storedMimeType = $mimeType;
        }

        $file = StoredFile::create(
            $id,
            $entityClass,
            $entityId,
            $purpose,
            $originalName,
            $storagePath,
            $storedMimeType,
            $size,
            $uploadedBy,
            $now,
        );

        $this->files->save($file);

        return $file;
    }

    /**
     * Renders every {@see ImageSize} variant from the uploaded source and stores
     * them under a per-avatar directory. Returns that directory as the entity's
     * storage path; an individual variant lives at "{path}/{size}.webp".
     */
    private function storeAvatarVariants(string $tmpPath, string $id): string
    {
        $source = @file_get_contents($tmpPath);

        if ($source === false) {
            throw new \RuntimeException('Unable to read the uploaded avatar.');
        }

        // No date sharding: an avatar is a single per-entity directory keyed by uuid.
        $directory = sprintf('avatar/%s', $id);

        foreach (ImageSize::cases() as $size) {
            $this->storage->writeContents(
                $this->imageProcessor->process($source, $size),
                $directory . '/' . $size->fileName(),
            );
        }

        return $directory;
    }

    private function removeExistingAvatar(string $entityClass, string $entityId): void
    {
        $existing = $this->files->findAvatar($entityClass, $entityId);

        if ($existing === null) {
            return;
        }

        foreach (ImageSize::cases() as $size) {
            $this->storage->delete($existing->storagePath() . '/' . $size->fileName());
        }

        $this->files->delete($existing);
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
