<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\ValueObject;

use App\TaskFeature\Domain\ValueObject\TaskId;
use PHPUnit\Framework\TestCase;

final class TaskIdTest extends TestCase
{
    public function testGenerateReturnsValidUuidV4(): void
    {
        $id = TaskId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->value(),
        );
    }

    public function testGenerateReturnsUniqueValues(): void
    {
        $this->assertNotSame(TaskId::generate()->value(), TaskId::generate()->value());
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = TaskId::fromString($uuid);

        $this->assertSame($uuid, $id->value());
    }

    public function testFromStringThrowsOnInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaskId::fromString('not-a-uuid');
    }

    public function testFromStringThrowsOnNonV4Uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // version 1 UUID
        TaskId::fromString('550e8400-e29b-11d4-a716-446655440000');
    }
}
