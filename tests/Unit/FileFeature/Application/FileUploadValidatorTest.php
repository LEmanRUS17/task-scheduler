<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Application;

use App\FileFeature\Application\DTORequestValidator\FileUploadValidator;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use PHPUnit\Framework\TestCase;

final class FileUploadValidatorTest extends TestCase
{
    private function validator(): FileUploadValidator
    {
        return new FileUploadValidator(
            avatarMaxSize: 1000,
            avatarMimeTypes: ['image/png', 'image/jpeg'],
            attachmentMaxSize: 5000,
            attachmentMimeTypes: ['image/png', 'application/pdf'],
        );
    }

    public function testValidAvatarPassesWithoutViolations(): void
    {
        self::assertSame([], $this->validator()->validate(FilePurpose::Avatar, 'image/png', 500));
    }

    public function testAvatarRejectsDisallowedMimeType(): void
    {
        $violations = $this->validator()->validate(FilePurpose::Avatar, 'application/pdf', 500);

        self::assertArrayHasKey('file', $violations);
    }

    public function testRejectsFileExceedingMaxSize(): void
    {
        $violations = $this->validator()->validate(FilePurpose::Avatar, 'image/png', 2000);

        self::assertArrayHasKey('file', $violations);
    }

    public function testRejectsEmptyFile(): void
    {
        $violations = $this->validator()->validate(FilePurpose::Avatar, 'image/png', 0);

        self::assertArrayHasKey('file', $violations);
    }

    public function testAttachmentAllowsWiderMimeAndSize(): void
    {
        self::assertSame([], $this->validator()->validate(FilePurpose::Attachment, 'application/pdf', 4000));
    }
}
