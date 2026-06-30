<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeature\Application\ApiService\TagApiService;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListMyTagsController
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 10;
    private const ALLOWED_LIMITS = [10, 20, 50];

    public function __construct(
        private readonly TagApiService $tagService,
        private readonly SearchServiceInterface $searchService,
        private readonly Security $security,
    ) {
    }

    #[Route('/tag', name: 'tag_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $ownerId = $securityUser->getDomainUser()->id()->value();

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) >= self::MIN_QUERY_LENGTH) {
            $result = $this->searchService->searchTags($query, $ownerId, $limit, $offset);
            $tags = $this->tagService->getByIds($result['ids']);
            $count = $result['total'];
        } else {
            $tags = $this->tagService->getMyTagsPage($ownerId, $limit, $offset);
            $count = $this->tagService->countMyTags($ownerId);
        }

        return new JsonResponse([
            'tags' => TagView::many($tags),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($count / $limit),
            ],
            'count' => $count,
        ]);
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }
}
