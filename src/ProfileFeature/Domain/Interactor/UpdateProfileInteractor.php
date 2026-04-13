<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Interactor;

use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use App\ProfileFeature\Domain\ValueObject\Username;

final class UpdateProfileInteractor
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
    ) {}

    public function update(
        string $userId,
        ?string $username,
        ?string $firstname,
        ?string $lastname,
        ?string $midlname,
        ?string $status,
    ): void {
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            throw new \DomainException("Profile for user {$userId} not found");
        }

        $profile->update(
            $username !== null ? Username::fromString($username) : null,
            $firstname,
            $lastname,
            $midlname,
            $status !== null ? ProfileStatus::fromString($status) : null,
        );

        $this->profiles->save($profile);
    }
}
