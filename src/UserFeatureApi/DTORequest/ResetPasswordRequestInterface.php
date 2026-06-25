<?php

declare(strict_types=1);

namespace App\UserFeatureApi\DTORequest;

interface ResetPasswordRequestInterface
{
    public function getEmail(): string;

    public function getCode(): string;

    public function getNewPassword(): string;
}
