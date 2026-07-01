<?php

declare(strict_types=1);

namespace App\TagFeature\Application\DTORequest;

use App\TagFeatureApi\DTORequest\UpdateTagRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTagRequestDTO implements UpdateTagRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(max: 64, maxMessage: 'Name must not exceed 64 characters')]
        private readonly string $name,
        #[Assert\NotBlank(message: 'Color is required')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Color must be a #RRGGBB hex value')]
        private readonly string $color,
        private readonly ?string $description = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
