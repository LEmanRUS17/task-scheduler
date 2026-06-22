<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Domain\Entity;

use App\FileFeature\Domain\Entity\StoredFile;
use App\FileFeature\Domain\ValueObject\FilePurpose;
use PHPUnit\Framework\TestCase;

final class StoredFileTest extends TestCase
{
    public function testCreateExposesAllProperties(): void
    {
        $createdAt = new \DateTimeImmutable('2026-06-22 10:00:00');

        $file = StoredFile::create(
            'file-1',
            'App\\Some\\Entity',
            'entity-1',
            FilePurpose::Attachment,
            'report.pdf',
            'attachment/2026/06/file-1.pdf',
            'application/pdf',
            1234,
            'user-1',
            $createdAt,
        );

        self::assertSame('file-1', $file->id());
        self::assertSame('App\\Some\\Entity', $file->entityClass());
        self::assertSame('entity-1', $file->entityId());
        self::assertSame(FilePurpose::Attachment, $file->purpose());
        self::assertSame('report.pdf', $file->originalName());
        self::assertSame('attachment/2026/06/file-1.pdf', $file->storagePath());
        self::assertSame('application/pdf', $file->mimeType());
        self::assertSame(1234, $file->size());
        self::assertSame('user-1', $file->uploadedBy());
        self::assertSame($createdAt, $file->createdAt());
    }
}
