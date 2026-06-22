<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Application;

use App\FileFeature\Application\ApiService\FileApiService;
use App\FileFeature\Application\DTORequestValidator\FileUploadValidator;
use App\FileFeature\Domain\Interactor\DeleteFileInteractor;
use App\FileFeature\Domain\Interactor\UploadFileInteractor;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class FileApiServiceTest extends TestCase
{
    private function validator(): FileUploadValidator
    {
        return new FileUploadValidator(
            avatarMaxSize: 1000,
            avatarMimeTypes: ['image/png'],
            attachmentMaxSize: 1000,
            attachmentMimeTypes: ['application/pdf'],
        );
    }

    public function testUploadAvatarStoresFileAndReturnsMetadata(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $repository->method('findAvatar')->willReturn(null);
        $storage = $this->createStub(FileStorageInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $metadata = $service->uploadAvatar('App\\Entity', 'e-1', '/tmp/x', 'a.png', 'image/png', 10, 'user-1');

        self::assertSame('avatar', $metadata->getPurpose());
        self::assertSame('a.png', $metadata->getOriginalName());
        self::assertSame('App\\Entity', $metadata->getEntityClass());
        self::assertSame('e-1', $metadata->getEntityId());
        self::assertNotSame('', $metadata->getId());
    }

    public function testUploadThrowsWhenValidationFails(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $storage = $this->createStub(FileStorageInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $this->expectException(\InvalidArgumentException::class);

        // Disallowed mime type for attachments -> validation fails before storing.
        $service->attach('App\\Entity', 'e-1', '/tmp/x', 'a.bin', 'application/x-foo', 10, 'user-1');
    }
}
