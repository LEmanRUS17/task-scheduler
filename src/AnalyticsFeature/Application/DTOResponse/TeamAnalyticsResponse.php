<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Application\DTOResponse;

use App\AnalyticsFeatureApi\DTOResponse\TeamAnalyticsResponseInterface;

final class TeamAnalyticsResponse implements TeamAnalyticsResponseInterface
{
    /**
     * @param array<array{status: string, avg_seconds: float}>    $avgTimePerStatus
     * @param array<array{day: string, count: int}>               $throughputPerDay
     * @param array<array{action: string, count: int}>            $crudActionsCount
     * @param array<array{day: string, status: string, count: int}> $statusTransitionsPerDay
     */
    public function __construct(
        private readonly array $avgTimePerStatus,
        private readonly int $completedCount,
        private readonly array $throughputPerDay,
        private readonly array $crudActionsCount,
        private readonly array $statusTransitionsPerDay,
    ) {
    }

    public function getAvgTimePerStatus(): array
    {
        return $this->avgTimePerStatus;
    }

    public function getCompletedCount(): int
    {
        return $this->completedCount;
    }

    public function getThroughputPerDay(): array
    {
        return $this->throughputPerDay;
    }

    public function getCrudActionsCount(): array
    {
        return $this->crudActionsCount;
    }

    public function getStatusTransitionsPerDay(): array
    {
        return $this->statusTransitionsPerDay;
    }
}
