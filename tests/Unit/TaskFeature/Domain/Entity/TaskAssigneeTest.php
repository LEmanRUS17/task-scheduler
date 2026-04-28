<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Domain\Entity;

use App\TaskFeature\Domain\Entity\TaskAssignee;
use App\TaskFeature\Domain\ValueObject\TaskId;
use PHPUnit\Framework\TestCase;

final class TaskAssigneeTest extends TestCase
{
    public function testAssignStoresFields(): void
    {
        $taskId = TaskId::generate();
        $assignedAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $assignee = TaskAssignee::assign($taskId, 'user-42', $assignedAt);

        $this->assertSame($taskId->value(), $assignee->taskId()->value());
        $this->assertSame('user-42', $assignee->userId());
        $this->assertSame($assignedAt, $assignee->assignedAt());
    }
}
