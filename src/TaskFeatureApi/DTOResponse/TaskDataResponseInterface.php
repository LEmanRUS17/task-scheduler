<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\DTOResponse;

interface TaskDataResponseInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getStatus(): string;
    public function getCreatedAt(): \DateTimeImmutable;
}
