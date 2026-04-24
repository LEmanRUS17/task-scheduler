<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTOResponse;

use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;

final class TaskResponseDTO implements TaskDataResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
