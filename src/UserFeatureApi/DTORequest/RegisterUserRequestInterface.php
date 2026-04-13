<?php

declare(strict_types=1);

namespace App\UserFeatureApi\DTORequest;

interface RegisterUserRequestInterface
{
    public function getEmail(): string;

    public function getPlainPassword(): string;
}
