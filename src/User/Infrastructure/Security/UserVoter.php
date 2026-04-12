<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserPermission;
use App\User\Domain\ValueObject\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter
{
    private const ATTRIBUTES = [
        UserPermission::VIEW,
        UserPermission::EDIT,
        UserPermission::DELETE,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $securityUser = $token->getUser();

        if (!$securityUser instanceof SecurityUser) {
            return false;
        }

        $currentUser = $securityUser->getDomainUser();

        /** @var User $subject */
        return match ($attribute) {
            UserPermission::VIEW => $this->canView($currentUser, $subject),
            UserPermission::EDIT => $this->canEdit($currentUser, $subject),
            UserPermission::DELETE => $this->canDelete($currentUser),
            default => false,
        };
    }

    private function canView(User $currentUser, User $subject): bool
    {
        return $currentUser->id()->equals($subject->id())
            || $this->hasRole($currentUser, Role::Admin);
    }

    private function canEdit(User $currentUser, User $subject): bool
    {
        return $currentUser->id()->equals($subject->id())
            || $this->hasRole($currentUser, Role::Admin);
    }

    private function canDelete(User $currentUser): bool
    {
        return $this->hasRole($currentUser, Role::Admin);
    }

    private function hasRole(User $user, Role $role): bool
    {
        return in_array($role->value, $user->roles(), true);
    }
}
