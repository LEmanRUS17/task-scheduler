<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\TaskFeature\Application\DTORequest\TaskCreateRequestDTO;
use App\TaskFeature\Presentation\Formatter\TaskResponseFormatter;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CreateTaskController
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/task/create', name: 'task_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] TaskCreateRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $creatorUserId = $securityUser->getDomainUser()->id()->value();

        try {
            $task = $this->taskService->create($request, $creatorUserId);
        } catch (\InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);

            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => is_array($errors) ? $errors : ['general' => [$e->getMessage()]],
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $tagsByTask = $this->tagService->getEntityTagsByIds(TagServiceInterface::TYPE_TASK, [$task->getId()]);

        return new JsonResponse(
            TaskResponseFormatter::format($task, $tagsByTask[$task->getId()] ?? []),
            Response::HTTP_CREATED,
        );
    }
}
