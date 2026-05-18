<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Application\ApiService;

use App\AnalyticsFeature\Application\DTOResponse\TeamAnalyticsResponse;
use App\AnalyticsFeature\Domain\Port\AnalyticsQueryInterface;
use App\AnalyticsFeatureApi\Contract\AnalyticsServiceInterface;
use App\AnalyticsFeatureApi\DTOResponse\TeamAnalyticsResponseInterface;

final class AnalyticsApiService implements AnalyticsServiceInterface
{
    private const FINAL_STATUS = 'done';

    public function __construct(private readonly AnalyticsQueryInterface $analyticsQuery) {}

    public function getTeamAnalytics(
        string $teamId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): TeamAnalyticsResponseInterface {
        return new TeamAnalyticsResponse(
            avgTimePerStatus: $this->analyticsQuery->avgTimePerStatus($teamId, $from, $to),
            completedCount: $this->analyticsQuery->completedCount($teamId, self::FINAL_STATUS, $from, $to),
            throughputPerDay: $this->analyticsQuery->throughputPerDay($teamId, self::FINAL_STATUS, $from, $to),
            crudActionsCount: $this->analyticsQuery->crudActionsCount($teamId, $from, $to),
        );
    }
}
