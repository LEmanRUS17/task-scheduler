<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTOResponse;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;

interface TaskStatusHistoryResponseInterface
{
    public function getId(): string;

    public function getTransitionId(): string;

    public function getToStatusLabel(): ?string;

    public function getProfile(): ?ProfileDataResponseInterface;

    public function getChangedAt(): \DateTimeImmutable;
}
