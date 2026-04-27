<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTOResponse;

interface TaskDataResponseInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getStatus(): string;
    public function getPriority(): string;
    public function getTeamId(): ?string;
    public function getCreatedBy(): string;
    /** @return string[] */
    public function getAssigneeIds(): array;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getScheduledStart(): ?\DateTimeImmutable;
    public function getScheduledEnd(): ?\DateTimeImmutable;
    public function getEstimatedTime(): ?int;
    public function getActualTime(): ?int;
}
