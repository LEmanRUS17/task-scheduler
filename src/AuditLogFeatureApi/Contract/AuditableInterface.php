<?php

declare(strict_types=1);

namespace App\AuditLogFeatureApi\Contract;

interface AuditableInterface
{
    /** Human-readable label for this entity, captured into the audit trail. Null if the entity has none. */
    public function auditTitle(): ?string;
}
