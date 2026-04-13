<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DataMapper;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;

final class ProfileDataResponse implements ProfileDataResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly ?string $username,
        private readonly ?string $firstname,
        private readonly ?string $lastname,
        private readonly ?string $midlname,
        private readonly ?string $status,
        private readonly ?\DateTimeImmutable $lastLogin,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getMidlname(): ?string
    {
        return $this->midlname;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }
}
