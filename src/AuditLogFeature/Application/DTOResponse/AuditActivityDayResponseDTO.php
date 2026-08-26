<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Application\DTOResponse;

use App\AuditLogFeatureApi\DTOResponse\AuditActivityDayResponseInterface;

final class AuditActivityDayResponseDTO implements AuditActivityDayResponseInterface
{
    public function __construct(
        private readonly string $day,
        private readonly int $count,
    ) {
    }

    public function getDay(): string
    {
        return $this->day;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
