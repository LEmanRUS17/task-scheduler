<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequest;

interface TeamInviteMemberRequestInterface
{
    public function getUserId(): ?string;

    public function getEmail(): ?string;

    public function getRole(): string;
}
