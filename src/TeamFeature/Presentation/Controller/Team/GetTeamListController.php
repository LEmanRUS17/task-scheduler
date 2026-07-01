<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTeamListController
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 10;
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly SearchServiceInterface $searchService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/list', name: 'team_list', methods: ['GET'])]
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
            [$teams, $count] = $this->search($request, $query, $userId, $limit, $offset);
        } else {
            $teams = $this->teamService->getPage($userId, $limit, $offset);
            $count = $this->teamService->countAll($userId);
        }

        $tagsByTeam = $this->tagService->getEntityTagsByIds(
            TagServiceInterface::TYPE_TEAM,
            array_map(static fn(TeamDataResponseInterface $team) => $team->getId(), $teams),
        );

        return new JsonResponse([
            'teams' => array_map(
                static fn(TeamDataResponseInterface $team) => [
                    'id' => $team->getId(),
                    'title' => $team->getTitle(),
                    'status' => $team->getStatus(),
                    'createdAt' => $team->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'description' => $team->getDescription(),
                    'tags' => array_map(
                        static fn(TagResponseInterface $tag): array => [
                            'id' => $tag->getId(),
                            'name' => $tag->getName(),
                            'color' => $tag->getColor(),
                        ],
                        $tagsByTeam[$team->getId()] ?? [],
                    ),
                ],
                $teams,
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
     * @return array{0: TeamDataResponseInterface[], 1: int}
     */
    private function search(Request $request, string $query, string $userId, int $limit, int $offset): array
    {
        $statuses = $this->parseStatuses($request);
        $ownedOnly = $request->query->getBoolean('owner');

        $result = $this->searchService->searchTeams($query, $userId, $statuses, $ownedOnly, $limit, $offset);

        return [$this->teamService->getByIds($result['ids']), $result['total']];
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
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
