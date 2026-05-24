<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Domain\ValueObject;

use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use PHPUnit\Framework\TestCase;

final class ProfileStatusTest extends TestCase
{
    public function testFromStringCreatesStatus(): void
    {
        $status = ProfileStatus::fromString('Working remotely');

        $this->assertSame('Working remotely', $status->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $status = ProfileStatus::fromString('  On vacation  ');

        $this->assertSame('On vacation', $status->value());
    }

    public function testFromStringAcceptsEmptyString(): void
    {
        // ProfileStatus does not forbid empty strings, unlike Username
        $status = ProfileStatus::fromString('');

        $this->assertSame('', $status->value());
    }

    public function testFromStringAccepts160Characters(): void
    {
        $status = ProfileStatus::fromString(str_repeat('a', 160));

        $this->assertSame(160, mb_strlen($status->value()));
    }

    public function testFromStringThrowsWhenExceeds160Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProfileStatus::fromString(str_repeat('a', 161));
    }
}
