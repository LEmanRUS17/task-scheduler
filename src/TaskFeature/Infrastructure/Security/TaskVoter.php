<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Security;

use App\TaskFeature\Domain\ValueObject\TaskPermission;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// TODO: a user must only work with their own tasks or tasks of a team they belong
//       to. Add a VIEW permission (creator, assignee or team member) and enforce it
//       in the read-side controllers, which currently have no check at all:
//       GetTask, GetTaskStatusHistory, ListTaskComments, AddTaskComment,
//       ListTaskAttachments, DownloadTaskAttachment, ListTaskFiles.
//       EDIT/DELETE should also account for team membership.
/** @extends Voter<string, TaskDataResponseInterface> */
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
