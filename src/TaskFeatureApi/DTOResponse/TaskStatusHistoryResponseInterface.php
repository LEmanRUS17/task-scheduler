<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTOResponse;

interface TaskStatusHistoryResponseInterface
{
    public function getId(): string;

    public function getTransitionId(): string;

    public function getChangedBy(): ?string;

    public function getChangedAt(): \DateTimeImmutable;
}
