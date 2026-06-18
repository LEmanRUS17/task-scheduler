<?php

declare(strict_types=1);

namespace App\SearchFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SearchTeamController
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
        private readonly Security $security,
    ) {
    }

    #[Route('/teams/search', name: 'teams_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');

        if (strlen($q) < 2) {
            return new JsonResponse(['message' => 'Query must be at least 2 characters.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $statuses = $this->parseStatuses($request);
        $ownedOnly = $request->query->getBoolean('owner');

        $results = $this->searchService->searchTeams($q, $userId, $statuses, $ownedOnly);

        return new JsonResponse([
            'results' => array_map(
                static fn($r) => [
                    'teamId' => $r->getTeamId(),
                    'title'  => $r->getTitle(),
                    'status' => $r->getStatus(),
                ],
                $results,
            ),
        ]);
    }

    /**
     * Accepts repeated (`?status=a&status=b`) and comma-separated (`?status=a,b`) values.
     *
     * @return list<string>
     */
    private function parseStatuses(Request $request): array
    {
        $raw = $request->query->all()['status'] ?? [];
        $values = is_array($raw) ? $raw : [$raw];

        $statuses = [];

        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            foreach (explode(',', $value) as $part) {
                $part = trim($part);

                if ($part !== '') {
                    $statuses[] = $part;
                }
            }
        }

        return $statuses;
    }
}
