<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
    ) {
    }

    #[Route('/tag/{id}', name: 'tag_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $tag = $this->tagService->getById($id);

        if ($tag === null) {
            return new JsonResponse(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(TagView::one($tag));
    }
}
