<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Security;

use App\UserFeature\Domain\ValueObject\UserStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Rejects authentication for users that are not in good standing.
 *
 * Runs on every authenticated request (json_login and jwt authenticators alike),
 * so a deactivated or soft-deleted user can neither log in nor use an existing
 * JWT to keep accessing the API.
 */
final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof SecurityUser) {
            return;
        }

        $domainUser = $user->getDomainUser();

        if ($domainUser->status() === UserStatus::DELETED || $domainUser->deletedAt() !== null) {
            throw new CustomUserMessageAccountStatusException('Account is no longer active.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
