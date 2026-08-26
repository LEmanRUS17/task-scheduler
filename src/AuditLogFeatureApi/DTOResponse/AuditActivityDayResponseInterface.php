<?php

declare(strict_types=1);

namespace App\AuditLogFeatureApi\DTOResponse;

interface AuditActivityDayResponseInterface
{
    /** Y-m-d */
    public function getDay(): string;

    public function getCount(): int;
}
