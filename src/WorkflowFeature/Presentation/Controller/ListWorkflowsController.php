<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows', name: 'workflow_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) >= self::MIN_QUERY_LENGTH) {
            [$workflows, $count] = $this->search($request, $userId, $query, $limit, $offset);
        } else {
            $workflows = $this->workflowService->getPage($limit, $offset, $userId);
            $count = $this->workflowService->countAll($userId);
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
    private function search(Request $request, string $userId, string $query, int $limit, int $offset): array
    {
        $ownedOnly = $request->query->getBoolean('owner');

        $result = $this->searchService->searchWorkflows($query, $userId, $ownedOnly, $limit, $offset);

        return [$this->workflowService->getByIds($result['ids']), $result['total']];
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }
}
