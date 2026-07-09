<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\ValueObject;

use App\CommentFeature\Domain\ValueObject\CommentableType;
use PHPUnit\Framework\TestCase;

final class CommentableTypeTest extends TestCase
{
    public function testAcceptsAnySlugWithoutWhitelist(): void
    {
        $this->assertSame('task', CommentableType::fromString('task')->value());
        $this->assertSame('sprint_report', CommentableType::fromString('sprint_report')->value());
        $this->assertSame('release-note', CommentableType::fromString('release-note')->value());
    }

    public function testNormalizesCaseAndWhitespace(): void
    {
        $this->assertSame('task', CommentableType::fromString('  TaSk ')->value());
    }

    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentableType::fromString('   ');
    }

    public function testRejectsInvalidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentableType::fromString('App\Entity\Task');
    }

    public function testRejectsValueStartingWithDigit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentableType::fromString('1task');
    }

    public function testRejectsTooLongValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentableType::fromString(str_repeat('a', 33));
    }
}
