<?php

declare(strict_types=1);

namespace App\UserFeatureApi\Service;

use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;

interface UserServiceInterface
{
    public function register(RegisterUserRequestInterface $request): void;

    public function findById(string $id): ?UserDataResponseInterface;
}
