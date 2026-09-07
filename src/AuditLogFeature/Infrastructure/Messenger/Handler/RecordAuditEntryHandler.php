<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Messenger\Handler;

use App\AuditLogFeature\Domain\Port\AuditEntryStorageInterface;
use App\AuditLogFeature\Infrastructure\Messenger\Message\RecordAuditEntryMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordAuditEntryHandler
{
    public function __construct(private readonly AuditEntryStorageInterface $storage)
    {
    }

    public function __invoke(RecordAuditEntryMessage $message): void
    {
        $this->storage->record(
            id: $message->id,
            entityClass: $message->entityClass,
            entityId: $message->entityId,
            action: $message->action,
            changedData: $message->changedData,
            actorId: $message->actorId,
            occurredAt: $message->occurredAt,
            title: $message->title,
        );
    }
}
