<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\TaskFeature\Application\DTORequest\TaskCreateRequestDTO;
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
        private readonly Security $security,
    ) {}

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

            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            
            return new JsonResponse(
                [
                    'success' => false,
                    'variant' => 'danger',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'variant' => 'success',
                'data' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                ],
            ],
            Response::HTTP_CREATED,
        );
    }
}
