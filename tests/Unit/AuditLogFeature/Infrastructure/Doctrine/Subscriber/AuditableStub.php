<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Infrastructure\Doctrine\Subscriber;

use App\AuditLogFeatureApi\Contract\AuditableInterface;

class AuditableStub implements AuditableInterface
{
    public function auditTitle(): ?string
    {
        return 'stub-title';
    }
}
