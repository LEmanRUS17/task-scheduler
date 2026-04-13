<?php

declare(strict_types=1);

namespace App\ProfileFeatureApi\Service;

use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;
use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;

interface ProfileServiceInterface
{
    public function createForUser(string $userId): void;

    public function getByUserId(string $userId): ProfileDataResponseInterface;

    public function update(string $userId, UpdateProfileRequestInterface $request): void;
}
