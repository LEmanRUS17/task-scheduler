<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Repository;

use App\ProfileFeature\Domain\Entity\Profile;

interface ProfileRepositoryInterface
{
    public function save(Profile $profile): void;

    public function findByUserId(string $userId): ?Profile;
}
