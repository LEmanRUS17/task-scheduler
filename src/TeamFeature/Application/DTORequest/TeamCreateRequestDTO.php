<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DTORequest;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TeamCreateRequestDTO implements TeamCreateRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            maxMessage: 'Title must not exceed 255 characters',
        )]
        private readonly string $title,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void {}
}
