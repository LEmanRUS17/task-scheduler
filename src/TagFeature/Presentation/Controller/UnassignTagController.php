<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Application\DTORequest\AssignTagRequestDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UnassignTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
    ) {
    }

    #[Route('/tag/{id}/assignments', name: 'tag_unassign', methods: ['DELETE'])]
    public function __invoke(string $id, #[MapRequestPayload] AssignTagRequestDTO $request): JsonResponse
    {
        try {
            $this->tagService->unassign($id, $request->getEntityType(), $request->getEntityId());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
