<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Repository;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\ValueObject\ProfileId;

interface ProfileRepositoryInterface
{
    public function save(Profile $profile): void;

    public function findById(ProfileId $id): ?Profile;

    public function findByUserId(string $userId): ?Profile;
}
