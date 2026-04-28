<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\ValueObject;

use App\TaskFeature\Domain\ValueObject\TaskTitle;
use PHPUnit\Framework\TestCase;

final class TaskTitleTest extends TestCase
{
    public function testFromStringReturnsTitle(): void
    {
        $title = TaskTitle::fromString('My Task');

        $this->assertSame('My Task', $title->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $title = TaskTitle::fromString('  trimmed  ');

        $this->assertSame('trimmed', $title->value());
    }

    public function testFromStringThrowsOnEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaskTitle::fromString('');
    }

    public function testFromStringThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaskTitle::fromString('   ');
    }
}
