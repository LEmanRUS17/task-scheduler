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
final class SearchWorkflowController
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows/search', name: 'workflows_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');

        if (strlen($q) < 2) {
            return new JsonResponse(['message' => 'Query must be at least 2 characters.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $ownedOnly = $request->query->getBoolean('owner');

        $results = $this->searchService->searchWorkflows($q, $userId, $ownedOnly);

        return new JsonResponse([
            'results' => array_map(
                static fn($r) => [
                    'id'    => $r->getWorkflowId(),
                    'title' => $r->getTitle(),
                ],
                $results,
            ),
        ]);
    }
}
