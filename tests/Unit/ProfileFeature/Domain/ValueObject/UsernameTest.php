<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Domain\ValueObject;

use App\ProfileFeature\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class UsernameTest extends TestCase
{
    public function testFromStringCreatesUsername(): void
    {
        $username = Username::fromString('john_doe');

        $this->assertSame('john_doe', $username->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $username = Username::fromString('  john_doe  ');

        $this->assertSame('john_doe', $username->value());
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Username::fromString('');
    }

    public function testFromStringThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Username::fromString('   ');
    }

    public function testFromStringAccepts50Characters(): void
    {
        $username = Username::fromString(str_repeat('a', 50));

        $this->assertSame(50, mb_strlen($username->value()));
    }

    public function testFromStringThrowsWhenExceeds50Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Username::fromString(str_repeat('a', 51));
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $a = Username::fromString('john_doe');
        $b = Username::fromString('john_doe');

        $this->assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $a = Username::fromString('john_doe');
        $b = Username::fromString('jane_doe');

        $this->assertFalse($a->equals($b));
    }
}
