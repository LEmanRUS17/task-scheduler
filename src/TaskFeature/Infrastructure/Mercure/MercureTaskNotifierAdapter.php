<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Mercure;

use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Port\TaskNotifierInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MercureTaskNotifierAdapter implements TaskNotifierInterface
{
    public function __construct(private readonly HubInterface $hub) {}

    public function notifyStatusChanged(TaskStatusChanged $event): void
    {
        $topics = ["/tasks/{$event->taskId}"];
        if ($event->teamId !== null) {
            $topics[] = "/teams/{$event->teamId}/tasks";
        }

        $this->hub->publish(new Update(
            topics: $topics,
            data: json_encode([
                'type'       => 'task.status_changed',
                'taskId'     => $event->taskId,
                'fromStatus' => $event->fromStatus,
                'toStatus'   => $event->toStatus,
                'workflow'   => $event->workflowDefinitionTitle,
            ]),
        ));
    }
}
