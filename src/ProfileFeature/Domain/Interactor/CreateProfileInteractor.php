<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Interactor;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Port\ClockInterface;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeature\Domain\ValueObject\Username;

final class CreateProfileInteractor
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly ClockInterface $clock,
    ) {}

    public function create(string $userId): void
    {
        if ($this->profiles->findByUserId($userId) !== null) {
            throw new \DomainException("Profile for user {$userId} already exists");
        }

        $profile = Profile::create(
            $userId,
            Username::fromString('user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8)),
            $this->clock->now(),
        );

        $this->profiles->save($profile);
    }
}
