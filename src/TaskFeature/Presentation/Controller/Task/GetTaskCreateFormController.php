<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Controller\Task;

use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetTaskCreateFormController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly Security $security,
    ) {
    }

    #[Route('/task/create', name: 'task_create_form', methods: ['GET'], priority: 1)]
    public function __invoke(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $defaultWorkflow = $this->workflowService->getDefaultForUser($userId);

        return new JsonResponse([
            'title' => '',
            'workflow' => $defaultWorkflow?->getId(),
            'priority' => null,
            'teamId' => null,
            'assigneeIds' => [],
            'tagIds' => [],
            'scheduledStart' => null,
            'scheduledEnd' => null,
            'estimatedTime' => null,
            'description' => null,
        ]);
    }
}
