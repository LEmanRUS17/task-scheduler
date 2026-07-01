<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Controller\Team;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\TeamFeature\Application\DTORequest\TeamCreateRequestDTO;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CreateTeamController
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly TagServiceInterface $tagService,
        private readonly Security $security,
    ) {
    }

    #[Route('/team/create', name: 'team_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] TeamCreateRequestDTO $request,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $creatorUserId = $securityUser->getDomainUser()->id()->value();

        try {
            $team = $this->teamService->create($request, $creatorUserId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'message' => 'Validation failed',
                    'errors' => json_decode($e->getMessage(), true),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $tagsByTeam = $this->tagService->getEntityTagsByIds(TagServiceInterface::TYPE_TEAM, [$team->getId()]);

        return new JsonResponse(
            [
                'id' => $team->getId(),
                'title' => $team->getTitle(),
                'status' => $team->getStatus(),
                'description' => $team->getDescription(),
                'tags' => array_map(
                    static fn(TagResponseInterface $tag): array => [
                        'id' => $tag->getId(),
                        'name' => $tag->getName(),
                        'color' => $tag->getColor(),
                    ],
                    $tagsByTeam[$team->getId()] ?? [],
                ),
            ],
            Response::HTTP_CREATED,
        );
    }
}
