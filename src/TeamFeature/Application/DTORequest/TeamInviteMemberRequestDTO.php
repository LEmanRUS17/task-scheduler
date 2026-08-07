<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamInviteMemberRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TeamInviteMemberRequestDTO implements TeamInviteMemberRequestInterface
{
    public function __construct(
        #[Assert\Uuid(message: 'Invalid user ID format')]
        private readonly ?string $userId = null,
        #[Assert\Email(message: 'Invalid email format')]
        private readonly ?string $email = null,
        #[Assert\NotBlank(message: 'Role is required')]
        #[Assert\Choice(choices: ['member', 'owner'], message: 'Role must be "member" or "owner"')]
        private readonly string $role = 'member',
    ) {
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
