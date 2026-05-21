<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTOResponse;

use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;

final class UserResponseDTO implements UserDataResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
