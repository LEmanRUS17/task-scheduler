<?php

declare(strict_types=1);

namespace App\ProfileFeatureApi\DTOResponse;

interface ProfileDataResponseInterface
{
    public function getUserId(): string;

    public function getUsername(): ?string;

    public function getFirstname(): ?string;

    public function getLastname(): ?string;

    public function getMidlname(): ?string;

    public function getStatus(): ?string;

    public function getLastLogin(): ?\DateTimeImmutable;
}
