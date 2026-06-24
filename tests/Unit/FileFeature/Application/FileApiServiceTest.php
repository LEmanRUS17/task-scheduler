<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Application;

use App\FileFeature\Application\ApiService\FileApiService;
use App\FileFeature\Application\DTORequestValidator\FileUploadValidator;
use App\FileFeature\Domain\Interactor\DeleteFileInteractor;
use App\FileFeature\Domain\Interactor\UploadFileInteractor;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Port\ImageProcessorInterface;
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

    private function buildService(?FileStorageInterface $storage = null): FileApiService
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $storage ??= $this->createStub(FileStorageInterface::class);
        $processor = $this->createStub(ImageProcessorInterface::class);

        return new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );
    }

    public function testUploadAvatarRendersVariantsAndReturnsMetadata(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $repository->method('findAvatar')->willReturn(null);
        $storage = $this->createMock(FileStorageInterface::class);
        // One write per generated size variant (Small, Medium, Large).
        $storage->expects(self::exactly(3))->method('writeContents');

        $processor = $this->createStub(ImageProcessorInterface::class);
        $processor->method('process')->willReturn('webp-bytes');

        $tmp = tempnam(sys_get_temp_dir(), 'avatar');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'source-image-bytes');

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $metadata = $service->uploadAvatar('App\\Entity', 'e-1', $tmp, 'a.png', 'image/png', 10, 'user-1');

        self::assertSame('avatar', $metadata->getPurpose());
        self::assertSame('image/webp', $metadata->getMimeType());
        self::assertSame('App\\Entity', $metadata->getEntityClass());
        self::assertSame('e-1', $metadata->getEntityId());
        self::assertNotSame('', $metadata->getId());

        @unlink($tmp);
    }

    public function testValidateAttachmentReturnsNoViolationsForAllowedFile(): void
    {
        $service = $this->buildService();

        self::assertSame([], $service->validateAttachment('application/pdf', 500));
    }

    public function testValidateAttachmentReportsViolationsWithoutStoring(): void
    {
        $storage = $this->createMock(FileStorageInterface::class);
        $storage->expects(self::never())->method('store');
        $storage->expects(self::never())->method('writeContents');

        $service = $this->buildService($storage);

        // Disallowed mime + oversized -> violations, but nothing is written.
        $violations = $service->validateAttachment('application/x-foo', 5000);

        self::assertArrayHasKey('file', $violations);
        self::assertNotEmpty($violations['file']);
    }

    public function testUploadThrowsWhenValidationFails(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $storage = $this->createStub(FileStorageInterface::class);
        $processor = $this->createStub(ImageProcessorInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
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
