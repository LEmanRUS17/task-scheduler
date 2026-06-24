<?php

declare(strict_types=1);

namespace App\FileFeature\Application\ApiService;

use App\FileFeature\Application\DataMapper\FileMetadataResponse;
use App\FileFeature\Application\DTORequestValidator\FileUploadValidator;
use App\FileFeature\Domain\Interactor\DeleteFileInteractor;
use App\FileFeature\Domain\Interactor\UploadFileInteractor;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use App\FileFeatureApi\Contract\FileMetadataInterface;
use App\FileFeatureApi\Contract\FileServiceInterface;
use App\FileFeatureApi\Contract\ImageSize;

final class FileApiService implements FileServiceInterface
{
    public function __construct(
        private readonly UploadFileInteractor $uploadInteractor,
        private readonly DeleteFileInteractor $deleteInteractor,
        private readonly FileRepositoryInterface $files,
        private readonly FileStorageInterface $storage,
        private readonly FileUploadValidator $validator,
    ) {
    }

    public function uploadAvatar(
        string $entityClass,
        string $entityId,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): FileMetadataInterface {
        return $this->upload(
            FilePurpose::Avatar,
            $entityClass,
            $entityId,
            $tmpPath,
            $originalName,
            $mimeType,
            $size,
            $uploadedBy,
        );
    }

    public function getAvatar(string $entityClass, string $entityId): ?FileMetadataInterface
    {
        $file = $this->files->findAvatar($entityClass, $entityId);

        return $file !== null ? FileMetadataResponse::fromEntity($file) : null;
    }

    public function avatarImagePath(
        string $entityClass,
        string $entityId,
        ImageSize $size = ImageSize::Large,
    ): ?string {
        $file = $this->files->findAvatar($entityClass, $entityId);

        if ($file === null) {
            return null;
        }

        return $this->storage->absolutePath($file->storagePath() . '/' . $size->fileName());
    }

    public function deleteAvatar(string $entityClass, string $entityId): void
    {
        $file = $this->files->findAvatar($entityClass, $entityId);

        if ($file !== null) {
            $this->deleteInteractor->delete($file);
        }
    }

    public function attach(
        string $entityClass,
        string $entityId,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): FileMetadataInterface {
        return $this->upload(
            FilePurpose::Attachment,
            $entityClass,
            $entityId,
            $tmpPath,
            $originalName,
            $mimeType,
            $size,
            $uploadedBy,
        );
    }

    public function validateAttachment(string $mimeType, int $size): array
    {
        return $this->validator->validate(FilePurpose::Attachment, $mimeType, $size);
    }

    public function listAttachments(string $entityClass, string $entityId): array
    {
        return array_map(
            static fn ($file) => FileMetadataResponse::fromEntity($file),
            $this->files->findAttachments($entityClass, $entityId),
        );
    }

    public function getFile(string $fileId): ?FileMetadataInterface
    {
        $file = $this->files->findById($fileId);

        return $file !== null ? FileMetadataResponse::fromEntity($file) : null;
    }

    public function absolutePath(string $fileId): ?string
    {
        $file = $this->files->findById($fileId);

        return $file !== null ? $this->storage->absolutePath($file->storagePath()) : null;
    }

    public function deleteFile(string $fileId): void
    {
        $file = $this->files->findById($fileId);

        if ($file !== null) {
            $this->deleteInteractor->delete($file);
        }
    }

    private function upload(
        FilePurpose $purpose,
        string $entityClass,
        string $entityId,
        string $tmpPath,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedBy,
    ): FileMetadataInterface {
        $violations = $this->validator->validate($purpose, $mimeType, $size);

        if (!empty($violations)) {
            throw new \InvalidArgumentException((string) json_encode($violations));
        }

        $file = $this->uploadInteractor->upload(
            $entityClass,
            $entityId,
            $purpose,
            $tmpPath,
            $originalName,
            $mimeType,
            $size,
            $uploadedBy,
        );

        return FileMetadataResponse::fromEntity($file);
    }
}
