<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DTORequest;

use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequestDTO implements UpdateProfileRequestInterface
{
    public function __construct(
        #[Assert\Length(max: 50, maxMessage: 'Username must not exceed 50 characters')]
        private readonly ?string $username = null,

        #[Assert\Length(max: 255, maxMessage: 'Firstname must not exceed 255 characters')]
        private readonly ?string $firstname = null,

        #[Assert\Length(max: 255, maxMessage: 'Lastname must not exceed 255 characters')]
        private readonly ?string $lastname = null,

        #[Assert\Length(max: 255, maxMessage: 'Midlname must not exceed 255 characters')]
        private readonly ?string $midlname = null,

        #[Assert\Length(max: 160, maxMessage: 'Status must not exceed 160 characters')]
        private readonly ?string $status = null,
    ) {}

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
}
