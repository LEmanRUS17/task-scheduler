<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Application\DTOResponse;

use App\AuditLogFeatureApi\DTOResponse\AuditEntryResponseInterface;

final class AuditEntryResponseDTO implements AuditEntryResponseInterface
{
    /** @param array<string, array{0: mixed, 1: mixed}> $changedData */
    public function __construct(
        private readonly string $id,
        private readonly string $entityClass,
        private readonly string $entityId,
        private readonly string $action,
        private readonly array $changedData,
        private readonly ?string $actorId,
        private readonly \DateTimeImmutable $occurredAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getChangedData(): array
    {
        return $this->changedData;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
