<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Http\Request;

use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest implements RegisterUserRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
        private readonly string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Password must be at least 8 characters',
        )]
        private readonly string $plainPassword,
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }
}
