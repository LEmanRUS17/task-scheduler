<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommentFeature\Domain\ValueObject;

use App\CommentFeature\Domain\ValueObject\CommentContent;
use PHPUnit\Framework\TestCase;

final class CommentContentTest extends TestCase
{
    public function testTrimsValue(): void
    {
        $this->assertSame('Hello', CommentContent::fromString('  Hello  ')->value());
    }

    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentContent::fromString('   ');
    }

    public function testRejectsTooLongValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommentContent::fromString(str_repeat('a', 10001));
    }

    public function testAcceptsMaxLengthValue(): void
    {
        $content = CommentContent::fromString(str_repeat('a', 10000));

        $this->assertSame(10000, mb_strlen($content->value()));
    }
}
