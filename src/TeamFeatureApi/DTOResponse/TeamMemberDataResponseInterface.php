<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTOResponse;

interface TeamMemberDataResponseInterface
{
    public function getTeamId(): string;
    public function getUserId(): string;
    public function getRole(): string;
    public function getJoinedAt(): \DateTimeImmutable;
}
