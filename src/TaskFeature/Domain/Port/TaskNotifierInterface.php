<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Port;

use App\TaskFeature\Domain\Event\TaskStatusChanged;

interface TaskNotifierInterface
{
    public function notifyStatusChanged(TaskStatusChanged $event): void;
}
