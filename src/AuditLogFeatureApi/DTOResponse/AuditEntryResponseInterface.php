<?php

declare(strict_types=1);

namespace App\AuditLogFeatureApi\DTOResponse;

interface AuditEntryResponseInterface
{
    public function getId(): string;

    public function getEntityClass(): string;

    public function getEntityId(): string;

    public function getAction(): string;

    /** @return array<string, array{0: mixed, 1: mixed}> */
    public function getChangedData(): array;

    public function getActorId(): ?string;

    public function getOccurredAt(): \DateTimeImmutable;

    public function getTitle(): ?string;

    public function getEventType(): ?string;
}
