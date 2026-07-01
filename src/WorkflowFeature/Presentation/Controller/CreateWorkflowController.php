<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Presentation\Controller;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\WorkflowFeature\Application\DTORequest\CreateWorkflowRequestDTO;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CreateWorkflowController
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/workflows', name: 'workflow_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateWorkflowRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $createdBy = $securityUser->getDomainUser()->id()->value();

        try {
            $workflow = $this->workflowService->create($request, $createdBy);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        $tagsByWorkflow = $this->tagService->getEntityTagsByIds(
            TagServiceInterface::TYPE_WORKFLOW,
            [$workflow->getId()],
        );

        return new JsonResponse(
            [
                'id' => $workflow->getId(),
                'title' => $workflow->getTitle(),
                'createdBy' => $workflow->getCreatedBy(),
                'createdAt' => $workflow->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'description' => $workflow->getDescription(),
                'tags' => array_map(
                    static fn(TagResponseInterface $tag): array => [
                        'id' => $tag->getId(),
                        'name' => $tag->getName(),
                        'color' => $tag->getColor(),
                    ],
                    $tagsByWorkflow[$workflow->getId()] ?? [],
                ),
            ],
            Response::HTTP_CREATED,
        );
    }
}
