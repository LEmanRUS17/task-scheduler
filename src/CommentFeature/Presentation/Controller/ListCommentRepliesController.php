<?php

declare(strict_types=1);

namespace App\CommentFeature\Presentation\Controller;

use App\CommentFeature\Application\ApiService\CommentApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ListCommentRepliesController
{
    public function __construct(
        private readonly CommentApiService $commentService,
    ) {
    }

    #[Route('/comment/{id}/replies', name: 'comment_replies_list', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            if ($this->commentService->getById($id) === null) {
                return new JsonResponse(['message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            $replies = $this->commentService->getReplies($id);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['replies' => CommentView::many($replies)]);
    }
}
