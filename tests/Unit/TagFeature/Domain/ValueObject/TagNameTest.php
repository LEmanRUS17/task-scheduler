<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Domain\ValueObject;

use App\TagFeature\Domain\ValueObject\TagName;
use PHPUnit\Framework\TestCase;

final class TagNameTest extends TestCase
{
    public function testTrimsValue(): void
    {
        $this->assertSame('urgent', TagName::fromString('  urgent ')->value());
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TagName::fromString('   ');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TagName::fromString(str_repeat('a', 65));
    }
}
