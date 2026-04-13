<?php

declare(strict_types=1);

namespace App\UserFeatureApi\Service;

use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;

interface UserServiceInterface
{
    public function register(RegisterUserRequestInterface $request): void;
}
