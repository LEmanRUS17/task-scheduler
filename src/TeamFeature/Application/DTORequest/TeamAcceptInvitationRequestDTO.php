<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamAcceptInvitationRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TeamAcceptInvitationRequestDTO implements TeamAcceptInvitationRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Token is required')]
        private readonly string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
