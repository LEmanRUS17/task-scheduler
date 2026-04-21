<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Domain\ValueObject;

use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;

final class WorkflowTitleTest extends TestCase
{
    public function testFromStringCreatesInstance(): void
    {
        $title = WorkflowTitle::fromString('My Workflow');

        $this->assertSame('My Workflow', $title->value());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $title = WorkflowTitle::fromString('  My Workflow  ');

        $this->assertSame('My Workflow', $title->value());
    }

    public function testFromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WorkflowTitle::fromString('');
    }

    public function testFromStringThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WorkflowTitle::fromString('   ');
    }

    public function testFromStringThrowsWhenExceeds255Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WorkflowTitle::fromString(str_repeat('a', 256));
    }

    public function testFromStringAccepts255Characters(): void
    {
        $title = WorkflowTitle::fromString(str_repeat('a', 255));

        $this->assertSame(255, strlen($title->value()));
    }
}
