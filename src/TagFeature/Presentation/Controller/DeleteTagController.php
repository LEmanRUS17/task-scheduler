<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeature\Application\ApiService\TagApiService;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteTagController
{
    public function __construct(
        private readonly TagApiService $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/tag/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $ownerId = $securityUser->getDomainUser()->id()->value();

        $deleted = $this->tagService->delete($id, $ownerId);

        if (!$deleted) {
            return new JsonResponse(['message' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
