<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Messenger\Handler;

use App\AnalyticsFeature\Domain\Port\TaskActionStorageInterface;
use App\AnalyticsFeature\Infrastructure\Messenger\Message\RecordTaskActionMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordTaskActionHandler
{
    public function __construct(private readonly TaskActionStorageInterface $storage)
    {
    }

    public function __invoke(RecordTaskActionMessage $message): void
    {
        $this->storage->record(
            taskId: $message->taskId,
            action: $message->action,
            actorId: $message->actorId,
            metadata: $message->metadata,
            occurredAt: $message->occurredAt,
        );
    }
}
