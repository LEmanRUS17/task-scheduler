<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTORequest;

interface TaskUpdateRequestInterface
{
    public function getTitle(): ?string;

    public function getPriority(): ?string;

    public function getScheduledStart(): ?\DateTimeImmutable;

    public function getScheduledEnd(): ?\DateTimeImmutable;

    public function getEstimatedTime(): ?int;
}
