<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Password must be at least 8 characters',
        )]
        public readonly string $plainPassword,
    ) {}
}
