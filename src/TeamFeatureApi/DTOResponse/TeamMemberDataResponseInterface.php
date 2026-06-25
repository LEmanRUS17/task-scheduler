<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTOResponse;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;

interface TeamMemberDataResponseInterface
{
    public function getTeamId(): string;
    public function getUserId(): string;
    public function getRole(): string;
    public function getJoinedAt(): \DateTimeImmutable;

    /** Profile (incl. avatar) of the user this membership belongs to, or null when unavailable. */
    public function getProfile(): ?ProfileDataResponseInterface;
}
