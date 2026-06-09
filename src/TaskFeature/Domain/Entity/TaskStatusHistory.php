<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Entity;

final class TaskStatusHistory
{
    private string $id;
    private string $taskId;
    private string $transitionId;
    private ?string $changedBy;
    private \DateTimeImmutable $changedAt;

    private function __construct(
        string $id,
        string $taskId,
        string $transitionId,
        ?string $changedBy,
        \DateTimeImmutable $changedAt,
    ) {
        $this->id = $id;
        $this->taskId = $taskId;
        $this->transitionId = $transitionId;
        $this->changedBy = $changedBy;
        $this->changedAt = $changedAt;
    }

    public static function record(
        string $id,
        string $taskId,
        string $transitionId,
        ?string $changedBy,
        \DateTimeImmutable $changedAt,
    ): self {
        return new self($id, $taskId, $transitionId, $changedBy, $changedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function transitionId(): string
    {
        return $this->transitionId;
    }

    public function changedBy(): ?string
    {
        return $this->changedBy;
    }

    public function changedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }
}
