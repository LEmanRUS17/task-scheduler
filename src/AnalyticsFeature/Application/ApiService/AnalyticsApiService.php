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

    public function getAnalytics(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): TeamAnalyticsResponseInterface {
        return new TeamAnalyticsResponse(
            avgTimePerStatus: $this->analyticsQuery->avgTimePerStatus($from, $to),
            // completedCount: $this->analyticsQuery->completedCount(self::FINAL_STATUS, $from, $to),
            completedCount: 0,
            // throughputPerDay: $this->analyticsQuery->throughputPerDay(self::FINAL_STATUS, $from, $to),
            throughputPerDay: [],
            crudActionsCount: $this->analyticsQuery->crudActionsCount($from, $to),
            statusTransitionsPerDay: $this->analyticsQuery->statusTransitionsPerDay($from, $to),
        );
    }
}
