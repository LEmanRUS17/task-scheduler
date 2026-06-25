<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequest;

use App\UserFeatureApi\DTORequest\ChangePasswordRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordRequestDTO implements ChangePasswordRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Current password is required')]
        private readonly string $currentPassword,
        #[Assert\NotBlank(message: 'New password is required')]
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Password must be at least 8 characters',
        )]
        private readonly string $newPassword,
    ) {
    }

    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }
}
