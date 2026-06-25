<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequest;

use App\UserFeatureApi\DTORequest\ResetPasswordRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordRequestDTO implements ResetPasswordRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
        private readonly string $email,
        #[Assert\NotBlank(message: 'Reset code is required')]
        private readonly string $code,
        #[Assert\NotBlank(message: 'New password is required')]
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Password must be at least 8 characters',
        )]
        private readonly string $newPassword,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }
}
