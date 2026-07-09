<?php

declare(strict_types=1);

namespace App\CommentFeature\Presentation\Controller;

use App\CommentFeature\Application\ApiService\CommentApiService;
use App\CommentFeature\Domain\Exception\CommentAccessDeniedException;
use App\CommentFeature\Domain\Exception\CommentDeletedException;
use App\CommentFeature\Domain\Exception\CommentHasRepliesException;
use App\CommentFeature\Domain\Exception\CommentNotFoundException;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DeleteCommentController
{
    public function __construct(
        private readonly CommentApiService $commentService,
        private readonly Security $security,
    ) {
    }

    #[Route('/comment/{id}', name: 'comment_delete', methods: ['DELETE'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $authorId = $securityUser->getDomainUser()->id()->value();

        try {
            $this->commentService->delete($id, $authorId);
        } catch (CommentNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (CommentAccessDeniedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (CommentHasRepliesException | CommentDeletedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
