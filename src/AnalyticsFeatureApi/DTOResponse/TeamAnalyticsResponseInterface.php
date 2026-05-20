<?php

declare(strict_types=1);

namespace App\AnalyticsFeatureApi\DTOResponse;

interface TeamAnalyticsResponseInterface
{
    /** @return array<array{status: string, avg_seconds: float}> */
    public function getAvgTimePerStatus(): array;

    public function getCompletedCount(): int;

    /** @return array<array{day: string, count: int}> */
    public function getThroughputPerDay(): array;

    /** @return array<array{action: string, count: int}> */
    public function getCrudActionsCount(): array;

    /** @return array<array{day: string, status: string, count: int}> */
    public function getStatusTransitionsPerDay(): array;
}
