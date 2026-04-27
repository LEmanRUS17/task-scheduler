<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\ValueObject;

final class TaskPermission
{
    public const EDIT = 'TASK_EDIT';
    public const DELETE = 'TASK_DELETE';
}
