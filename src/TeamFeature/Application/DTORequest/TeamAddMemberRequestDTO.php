<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TeamAddMemberRequestDTO implements TeamAddMemberRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'User ID is required')]
        #[Assert\Uuid(message: 'Invalid user ID format')]
        private readonly string $userId,

        #[Assert\NotBlank(message: 'Role is required')]
        #[Assert\Choice(choices: ['member', 'owner'], message: 'Role must be "member" or "owner"')]
        private readonly string $role = 'member',
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
