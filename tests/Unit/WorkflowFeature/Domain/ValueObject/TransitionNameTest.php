<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\ValueObject;

use App\WorkflowFeature\Domain\ValueObject\TransitionName;
use PHPUnit\Framework\TestCase;

final class TransitionNameTest extends TestCase
{
    public function testFromStringCreatesInstance(): void
    {
        $name = TransitionName::fromString('start');

        $this->assertSame('start', $name->value());
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransitionName::fromString('');
    }

    public function testFromStringThrowsOnWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransitionName::fromString('go to done');
    }

    public function testFromStringThrowsWhenExceeds100Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransitionName::fromString(str_repeat('a', 101));
    }

    public function testFromStringAccepts100Characters(): void
    {
        $name = TransitionName::fromString(str_repeat('a', 100));

        $this->assertSame(100, strlen($name->value()));
    }
}
