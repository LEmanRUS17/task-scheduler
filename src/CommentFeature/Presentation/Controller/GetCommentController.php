<?php

declare(strict_types=1);

namespace App\CommentFeature\Presentation\Controller;

use App\CommentFeature\Application\ApiService\CommentApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetCommentController
{
    public function __construct(
        private readonly CommentApiService $commentService,
    ) {
    }

    #[Route('/comment/{id}', name: 'comment_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $comment = $this->commentService->getById($id);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($comment === null) {
            return new JsonResponse(['message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(CommentView::one($comment));
    }
}
