<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequest;

interface TeamAddMemberRequestInterface
{
    public function getUserId(): string;
    public function getRole(): string;
}
