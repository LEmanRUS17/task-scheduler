<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Application\DTORequest\UpdateTagRequestDTO;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/tag/{id}', name: 'tag_update', methods: ['PUT'])]
    public function __invoke(string $id, #[MapRequestPayload] UpdateTagRequestDTO $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $ownerId = $securityUser->getDomainUser()->id()->value();

        try {
            $tag = $this->tagService->update($id, $request, $ownerId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['message' => 'Validation failed', 'errors' => json_decode($e->getMessage(), true)],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        if ($tag === null) {
            return new JsonResponse(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(TagView::one($tag));
    }
}
