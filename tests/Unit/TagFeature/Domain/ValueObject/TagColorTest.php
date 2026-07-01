<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Domain\ValueObject;

use App\TagFeature\Domain\ValueObject\TagColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TagColorTest extends TestCase
{
    public function testAcceptsValidHexColor(): void
    {
        $this->assertSame('#ff8800', TagColor::fromString('#FF8800')->value());
    }

    public function testTrimsAndLowercases(): void
    {
        $this->assertSame('#abcdef', TagColor::fromString('  #ABCDEF ')->value());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidColors(): iterable
    {
        yield 'missing hash' => ['ff8800'];
        yield 'too short' => ['#fff'];
        yield 'non hex' => ['#gggggg'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidColors')]
    public function testRejectsInvalidColor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TagColor::fromString($value);
    }
}
