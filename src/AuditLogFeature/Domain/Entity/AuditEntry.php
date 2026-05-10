<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Entity;

final class AuditEntry
{
    private string $id;
    private string $entityClass;
    private string $entityId;
    private string $action;
    /** @var array<string, array{0: mixed, 1: mixed}> */
    private array $changedData;
    private ?string $actorId;
    private \DateTimeImmutable $occurredAt;

    private function __construct(
        string $id,
        string $entityClass,
        string $entityId,
        string $action,
        array $changedData,
        ?string $actorId,
        \DateTimeImmutable $occurredAt,
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->changedData = $changedData;
        $this->actorId = $actorId;
        $this->occurredAt = $occurredAt;
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changedData
     */
    public static function record(
        string $id,
        string $entityClass,
        string $entityId,
        string $action,
        array $changedData,
        ?string $actorId,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $entityClass, $entityId, $action, $changedData, $actorId, $occurredAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function entityClass(): string
    {
        return $this->entityClass;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function action(): string
    {
        return $this->action;
    }

    /** @return array<string, array{0: mixed, 1: mixed}> */
    public function changedData(): array
    {
        return $this->changedData;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
