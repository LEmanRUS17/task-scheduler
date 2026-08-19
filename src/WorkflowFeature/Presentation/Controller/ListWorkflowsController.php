<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListWorkflowsController
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 10;
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly SearchServiceInterface $searchService,
        private readonly TagServiceInterface $tagService,
        private readonly TeamMembershipInterface $membership,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows', name: 'workflow_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $teamId = $request->query->get('teamId') ?: null;

        if ($teamId !== null && !$this->membership->isMember($teamId, $userId)) {
            return new JsonResponse(['message' => 'Not a team member'], Response::HTTP_FORBIDDEN);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) >= self::MIN_QUERY_LENGTH) {
            [$workflows, $count] = $this->search($userId, $query, $limit, $offset);
        } else {
            $workflows = $this->workflowService->getPage($limit, $offset, $userId, $teamId);
            $count = $this->workflowService->countAll($userId, $teamId);
        }

        $tagsByWorkflow = $this->tagService->getEntityTagsByIds(
            TagServiceInterface::TYPE_WORKFLOW,
            array_map(static fn(WorkflowResponseInterface $w) => $w->getId(), $workflows),
        );

        return new JsonResponse([
            'workflow' => array_map(
                static fn(WorkflowResponseInterface $w) => [
                    'id' => $w->getId(),
                    'title' => $w->getTitle(),
                    'createdBy' => $w->getCreatedBy(),
                    'createdAt' => $w->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'isDefault' => $w->isDefault(),
                    'teamTitle' => $w->getTeamTitle(),
                    'tags' => array_map(
                        static fn(TagResponseInterface $tag): array => [
                            'id' => $tag->getId(),
                            'name' => $tag->getName(),
                            'color' => $tag->getColor(),
                        ],
                        $tagsByWorkflow[$w->getId()] ?? [],
                    ),
                ],
                $workflows,
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($count / $limit),
            ],
            'count' => $count,
        ]);
    }

    /**
     * @return array{0: WorkflowResponseInterface[], 1: int}
     */
    private function search(string $userId, string $query, int $limit, int $offset): array
    {
        $result = $this->searchService->searchWorkflows($query, $userId, $limit, $offset);

        return [$this->workflowService->getByIds($result['ids']), $result['total']];
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }
}
