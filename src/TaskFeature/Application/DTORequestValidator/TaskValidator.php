<?php

declare(strict_types=1);

namespace App\TaskFeature\Application\DTORequestValidator;

use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TaskValidator implements TaskValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly TeamMembershipInterface $teamMembership,
    ) {
    }

    /** @return array<string, string[]> */
    public function validate(TaskCreateRequestInterface $dto, string $userId): array
    {
        $violations = $this->collectViolations($dto);

        if ($dto->getTeamId() !== null && !$this->teamMembership->isMember($dto->getTeamId(), $userId)) {
            $violations['teamId'][] = 'User is not a member of the specified team';
        }

        if ($dto->getTeamId() !== null) {
            foreach ($dto->getAssigneeIds() as $assigneeId) {
                if (!$this->teamMembership->isMember($dto->getTeamId(), $assigneeId)) {
                    $violations['assigneeIds'][] = sprintf('User "%s" is not a member of the specified team', $assigneeId);
                }
            }
        }

        return $violations;
    }

    /** @return array<string, string[]> */
    public function validateUpdate(TaskUpdateRequestInterface $dto): array
    {
        return $this->collectViolations($dto);
    }

    /** @return array<string, string[]> */
    private function collectViolations(object $dto): array
    {
        $violations = [];

        foreach ($this->validator->validate($dto) as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
