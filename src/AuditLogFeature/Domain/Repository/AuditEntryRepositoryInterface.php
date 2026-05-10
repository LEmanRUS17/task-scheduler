<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Repository;

use App\AuditLogFeature\Domain\Entity\AuditEntry;

interface AuditEntryRepositoryInterface
{
    public function save(AuditEntry $entry): void;
}
