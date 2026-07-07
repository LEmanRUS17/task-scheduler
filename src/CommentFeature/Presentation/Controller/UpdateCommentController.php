<?php

declare(strict_types=1);

namespace App\CommentFeature\Presentation\Controller;

use App\CommentFeature\Application\ApiService\CommentApiService;
use App\CommentFeature\Application\DTORequest\UpdateCommentRequestDTO;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateCommentController
{
    public function __construct(
        private readonly CommentApiService $commentService,
        private readonly Security $security,
    ) {
    }

    #[Route('/comment/{id}', name: 'comment_update', methods: ['PATCH'])]
    public function __invoke(string $id, #[MapRequestPayload] UpdateCommentRequestDTO $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $authorId = $securityUser->getDomainUser()->id()->value();

        try {
            $comment = $this->commentService->update($id, $authorId, $request);
        } catch (CommentNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (CommentAccessDeniedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(CommentView::one($comment));
    }
}
