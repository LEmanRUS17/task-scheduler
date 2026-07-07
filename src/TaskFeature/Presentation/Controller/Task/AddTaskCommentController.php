<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\CommentFeatureApi\Contract\CommentServiceInterface;
use App\TaskFeature\Application\DTORequest\TaskCommentRequestDTO;
use App\TaskFeature\Presentation\Formatter\TaskCommentFormatter;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AddTaskCommentController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly CommentServiceInterface $commentService,
        private readonly Security $security,
    ) {
    }

    // The literal "task" segment takes precedence over the generic "/comment/{entityType}/{entityId}"
    // route, adding the task-existence check on top of it.
    #[Route('/comment/task/{id}', name: 'task_comment_add', methods: ['POST'])]
    public function __invoke(string $id, #[MapRequestPayload] TaskCommentRequestDTO $request): JsonResponse
    {
        if ($this->taskService->getById($id) === null) {
            return new JsonResponse(['message' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $authorId = $securityUser->getDomainUser()->id()->value();

        try {
            $comment = $this->commentService->add(
                TaskServiceInterface::COMMENTABLE_TYPE,
                $id,
                $authorId,
                $request,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(TaskCommentFormatter::format($comment), Response::HTTP_CREATED);
    }
}
