<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTOResponse;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;

interface TaskDataResponseInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getStatus(): string;
    public function getStatusId(): string;
    public function getPriority(): string;
    public function getTeamId(): ?string;
    public function getWorkflowId(): string;
    public function getCreatedBy(): string;
    /** Profile (incl. avatar) of the task creator, or null when unavailable. */
    public function getCreatedByProfile(): ?ProfileDataResponseInterface;
    /** @return string[] */
    public function getAssigneeIds(): array;
    /** @return array<string, ProfileDataResponseInterface> assignee profiles keyed by user id */
    public function getAssigneeProfiles(): array;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getScheduledStart(): ?\DateTimeImmutable;
    public function getScheduledEnd(): ?\DateTimeImmutable;
    public function getEstimatedTime(): ?int;
    public function getActualTime(): ?int;
    /** @return string[] */
    public function getAvailableTransitions(): array;
    public function getDescription(): ?string;
    public function isClosed(): bool;
    public function getClosedAt(): ?\DateTimeImmutable;
}
