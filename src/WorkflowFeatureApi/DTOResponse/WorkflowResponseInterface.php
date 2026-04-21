<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowResponseInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getCreatedBy(): string;

    public function getCreatedAt(): \DateTimeImmutable;
}
