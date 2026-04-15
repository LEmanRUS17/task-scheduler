<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Domain\ValueObject;

use App\UserFeature\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testFromStringCreatesEmail(): void
    {
        $email = Email::fromString('user@example.com');

        $this->assertSame('user@example.com', $email->value());
    }

    public function testFromStringNormalizesToLowercase(): void
    {
        $email = Email::fromString('User@EXAMPLE.COM');

        $this->assertSame('user@example.com', $email->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $email = Email::fromString('  user@example.com  ');

        $this->assertSame('user@example.com', $email->value());
    }

    public function testFromStringThrowsOnInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('not-an-email');
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('');
    }

    public function testFromStringThrowsOnMissingDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('user@');
    }
}
