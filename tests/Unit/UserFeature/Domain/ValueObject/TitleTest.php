<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Domain\ValueObject;

use App\TeamFeature\Domain\ValueObject\Title;
use PHPUnit\Framework\TestCase;

final class TitleTest extends TestCase
{
    public function testFromStringCreatesEmail(): void
    {
        $title = Title::fromString('test_team_create');

        $this->assertSame('test_team_create', $title->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $title = Title::fromString('  test_team_create  ');

        $this->assertSame('test_team_create', $title->value());
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Title::fromString('');
    }
}
