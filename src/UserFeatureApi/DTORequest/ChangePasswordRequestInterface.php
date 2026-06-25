<?php

declare(strict_types=1);

namespace App\UserFeatureApi\DTORequest;

interface ChangePasswordRequestInterface
{
    public function getCurrentPassword(): string;

    public function getNewPassword(): string;
}
