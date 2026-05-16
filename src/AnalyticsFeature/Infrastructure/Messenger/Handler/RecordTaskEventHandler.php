<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Messenger\Handler;

use App\AnalyticsFeature\Domain\Port\TaskEventStorageInterface;
use App\AnalyticsFeature\Infrastructure\Messenger\Message\RecordTaskEventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordTaskEventHandler
{
    public function __construct(private readonly TaskEventStorageInterface $storage) {}

    public function __invoke(RecordTaskEventMessage $message): void
    {
        $this->storage->record(
            taskId: $message->taskId,
            teamId: $message->teamId,
            fromStatus: $message->fromStatus,
            toStatus: $message->toStatus,
            occurredAt: $message->occurredAt,
        );
    }
}
