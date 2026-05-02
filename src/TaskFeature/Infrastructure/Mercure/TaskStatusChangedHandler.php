<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Mercure;

use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Port\TaskNotifierInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TaskStatusChangedHandler
{
    public function __construct(private readonly TaskNotifierInterface $notifier) {}

    public function __invoke(TaskStatusChanged $event): void
    {
        $this->notifier->notifyStatusChanged($event);
    }
}
