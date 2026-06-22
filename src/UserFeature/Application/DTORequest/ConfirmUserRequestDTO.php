<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DTORequest;

use App\UserFeatureApi\DTORequest\ConfirmUserRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmUserRequestDTO implements ConfirmUserRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
        private readonly string $email,
        #[Assert\NotBlank(message: 'Confirmation code is required')]
        private readonly string $code,
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
}
