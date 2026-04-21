<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\ValueObject;

use App\WorkflowFeature\Domain\ValueObject\StatusLabel;
use PHPUnit\Framework\TestCase;

final class StatusLabelTest extends TestCase
{
    public function testFromStringCreatesInstance(): void
    {
        $label = StatusLabel::fromString('open');

        $this->assertSame('open', $label->value());
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StatusLabel::fromString('');
    }

    public function testFromStringThrowsOnWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StatusLabel::fromString('in progress');
    }

    public function testFromStringThrowsOnLeadingWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StatusLabel::fromString(' open');
    }

    public function testFromStringThrowsWhenExceeds100Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StatusLabel::fromString(str_repeat('a', 101));
    }

    public function testFromStringAccepts100Characters(): void
    {
        $label = StatusLabel::fromString(str_repeat('a', 100));

        $this->assertSame(100, strlen($label->value()));
    }
}
