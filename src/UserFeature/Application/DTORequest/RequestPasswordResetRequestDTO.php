<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequest;

use App\UserFeatureApi\DTORequest\RequestPasswordResetRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class RequestPasswordResetRequestDTO implements RequestPasswordResetRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
        private readonly string $email,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
