<?php

declare(strict_types=1);

namespace App\SearchFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SearchTaskController
{
    public function __construct(private readonly SearchServiceInterface $searchService)
    {
    }

    #[Route('/tasks/search', name: 'tasks_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');

        if (strlen($q) < 2) {
            return new JsonResponse(['error' => 'Query must be at least 2 characters.'], Response::HTTP_BAD_REQUEST);
        }

        $teamId = $request->query->get('team_id');
        $status = $request->query->get('status');

        $results = $this->searchService->searchTasks($q, $teamId ?: null, $status ?: null);

        return new JsonResponse([
            'results' => array_map(
                static fn($r) => [
                    'taskId' => $r->getTaskId(),
                    'title'  => $r->getTitle(),
                    'status' => $r->getStatus(),
                ],
                $results,
            ),
        ]);
    }
}
