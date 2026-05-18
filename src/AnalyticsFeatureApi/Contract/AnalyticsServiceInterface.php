<?php

declare(strict_types=1);

namespace App\AnalyticsFeatureApi\Contract;

use App\AnalyticsFeatureApi\DTOResponse\TeamAnalyticsResponseInterface;

interface AnalyticsServiceInterface
{
    public function getTeamAnalytics(
        string $teamId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): TeamAnalyticsResponseInterface;
}
