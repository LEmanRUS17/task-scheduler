<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Security;

use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TaskVoter extends Voter
{
    private const ATTRIBUTES = [
        TaskPermission::EDIT,
        TaskPermission::DELETE,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true)
            && $subject instanceof TaskDataResponseInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $securityUser = $token->getUser();

        if (!$securityUser instanceof SecurityUser) {
            return false;
        }

        $userId = $securityUser->getDomainUser()->id()->value();

        /** @var TaskDataResponseInterface $subject */
        return match ($attribute) {
            TaskPermission::EDIT => $this->canEdit($userId, $subject),
            TaskPermission::DELETE => $this->canDelete($userId, $subject),
            default => false,
        };
    }

    private function canEdit(string $userId, TaskDataResponseInterface $task): bool
    {
        return $task->getCreatedBy() === $userId
            || in_array($userId, $task->getAssigneeIds(), true);
    }

    private function canDelete(string $userId, TaskDataResponseInterface $task): bool
    {
        return $task->getCreatedBy() === $userId;
    }
}
