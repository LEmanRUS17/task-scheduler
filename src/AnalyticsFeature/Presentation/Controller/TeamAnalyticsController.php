<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Presentation\Controller;

use App\AnalyticsFeatureApi\Contract\AnalyticsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class TeamAnalyticsController
{
    public function __construct(private readonly AnalyticsServiceInterface $analyticsService)
    {
    }

    #[Route('/analytics', name: 'analytics', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $fromParam = $request->query->get('from');
        $toParam = $request->query->get('to');

        if ($fromParam === null || $toParam === null) {
            return new JsonResponse([
                'error' => 'Parameters "from" and "to" are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $fromParam);
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', $toParam);

        if ($from === false || $to === false) {
            return new JsonResponse([
                'error' => 'Parameters "from" and "to" must be in Y-m-d format'
            ], Response::HTTP_BAD_REQUEST);
        }

        $from = $from->setTime(0, 0, 0);
        $to = $to->setTime(23, 59, 59);

        if ($from >= $to) {
            return new JsonResponse([
                'error' => 'Parameter "from" must be before "to"'
            ], Response::HTTP_BAD_REQUEST);
        }

        $analytics = $this->analyticsService->getAnalytics($from, $to);

        return new JsonResponse([
            'avg_time_per_status' => $analytics->getAvgTimePerStatus(),
            // 'completed_count' => $analytics->getCompletedCount(),
            // 'throughput_per_day' => $analytics->getThroughputPerDay(),
            'crud_actions_count' => $analytics->getCrudActionsCount(),
            'status_transitions_per_day' => $analytics->getStatusTransitionsPerDay(),
        ], Response::HTTP_OK);
    }
}
