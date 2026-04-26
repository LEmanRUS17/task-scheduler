<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Port;

use App\TaskFeature\Domain\Entity\Task;

interface TaskWorkflowInterface
{
    public function initialize(Task $task): void;

    public function applyTransition(Task $task, string $transition): void;

    public function canApply(Task $task, string $transition): bool;
}
