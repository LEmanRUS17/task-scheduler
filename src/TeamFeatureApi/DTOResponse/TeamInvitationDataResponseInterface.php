<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTOResponse;

interface TeamInvitationDataResponseInterface
{
    public function getId(): string;
    public function getTeamId(): string;
    public function getInvitedUserId(): string;
    public function getInvitedByUserId(): string;
    public function getRole(): string;
    public function getStatus(): string;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getExpiresAt(): \DateTimeImmutable;
}
