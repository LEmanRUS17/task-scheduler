<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DataMapper;

use App\ProfileFeatureApi\DTOResponse\AvatarReferenceInterface;

final class AvatarReference implements AvatarReferenceInterface
{
    public function __construct(
        private readonly string $url,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
