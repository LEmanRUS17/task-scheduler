<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Application\DTORequest\AssignTagRequestDTO;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AssignTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/tag/{id}/assignments', name: 'tag_assign', methods: ['POST'])]
    public function __invoke(string $id, #[MapRequestPayload] AssignTagRequestDTO $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $assignedBy = $securityUser->getDomainUser()->id()->value();

        try {
            $this->tagService->assign($id, $request->getEntityType(), $request->getEntityId(), $assignedBy);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
