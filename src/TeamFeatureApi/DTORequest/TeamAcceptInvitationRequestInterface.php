<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequest;

interface TeamAcceptInvitationRequestInterface
{
    public function getToken(): string;
}
