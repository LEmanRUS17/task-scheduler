<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Repository;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    /**
     * @param User $user
     *
     * @return void
     */
    public function save(User $user): void;

    /**
     * @param UserId $id
     *
     * @return User|null
     */
    public function findById(UserId $id): ?User;

    /**
     * @param Email $email
     *
     * @return User|null
     */
    public function findByEmail(Email $email): ?User;
}
