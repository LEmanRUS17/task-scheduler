<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListEntityTagsController
{
    public function __construct(
        private readonly TagApiService $tagService,
    ) {
    }

    #[Route('/taggables/{entityType}/{entityId}/tags', name: 'entity_tags_list', methods: ['GET'])]
    public function __invoke(string $entityType, string $entityId): JsonResponse
    {
        try {
            $tags = $this->tagService->getEntityTags($entityType, $entityId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['tags' => TagView::many($tags)]);
    }
}
