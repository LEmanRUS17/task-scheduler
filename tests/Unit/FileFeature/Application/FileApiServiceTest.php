<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Application;

use App\FileFeature\Application\ApiService\FileApiService;
use App\FileFeature\Application\DTORequestValidator\FileUploadValidator;
use App\FileFeature\Domain\Interactor\DeleteFileInteractor;
use App\FileFeature\Domain\Interactor\UploadFileInteractor;
use App\FileFeature\Domain\Port\FileStorageInterface;
use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\Port\ImageProcessorInterface;
use App\FileFeature\Domain\Repository\FileRepositoryInterface;
use App\FileFeature\Domain\ValueObject\FilePurpose;
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

    public function testListImageAttachmentsReturnsOnlyImages(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $repository->method('findAttachments')->willReturn([
            $this->makeStoredFile('f-1', 'a.png', 'image/png'),
            $this->makeStoredFile('f-2', 'doc.pdf', 'application/pdf'),
            $this->makeStoredFile('f-3', 'b.webp', 'image/webp'),
        ]);

        $storage = $this->createStub(FileStorageInterface::class);
        $processor = $this->createStub(ImageProcessorInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $images = $service->listImageAttachments('App\\Task', 't-1');

        self::assertCount(2, $images);
        self::assertSame(['f-1', 'f-3'], array_map(static fn ($f) => $f->getId(), $images));
    }

    public function testDeleteAttachmentsRemovesEveryAttachmentWhenNoFileId(): void
    {
        $repository = $this->createMock(FileRepositoryInterface::class);
        $repository->method('findAttachments')->willReturn([
            $this->makeStoredFile('f-1', 'a.png', 'image/png'),
            $this->makeStoredFile('f-2', 'doc.pdf', 'application/pdf'),
        ]);
        $repository->expects(self::exactly(2))->method('delete');

        $storage = $this->createStub(FileStorageInterface::class);
        $processor = $this->createStub(ImageProcessorInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $service->deleteAttachments('App\\Task', 't-1');
    }

    public function testDeleteAttachmentsRemovesOnlyTheGivenFile(): void
    {
        $repository = $this->createMock(FileRepositoryInterface::class);
        $repository->method('findAttachments')->willReturn([
            $this->makeStoredFile('f-1', 'a.png', 'image/png'),
            $this->makeStoredFile('f-2', 'doc.pdf', 'application/pdf'),
        ]);

        $deleted = [];
        $repository->expects(self::once())
            ->method('delete')
            ->willReturnCallback(static function (StoredFile $file) use (&$deleted): void {
                $deleted[] = $file->id();
            });

        $storage = $this->createStub(FileStorageInterface::class);
        $processor = $this->createStub(ImageProcessorInterface::class);

        $service = new FileApiService(
            new UploadFileInteractor($repository, $storage, $processor),
            new DeleteFileInteractor($repository, $storage),
            $repository,
            $storage,
            $this->validator(),
        );

        $service->deleteAttachments('App\\Task', 't-1', 'f-2');

        self::assertSame(['f-2'], $deleted);
    }

    private function makeStoredFile(string $id, string $name, string $mimeType): StoredFile
    {
        return StoredFile::create(
            $id,
            'App\\Task',
            't-1',
            FilePurpose::Attachment,
            $name,
            'attachment/2026/06/' . $id,
            $mimeType,
            128,
            'user-1',
            new \DateTimeImmutable('2026-06-24 10:00:00'),
        );
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
