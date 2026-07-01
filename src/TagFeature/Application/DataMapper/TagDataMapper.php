<?php

declare(strict_types=1);

namespace App\TagFeature\Application\DataMapper;

use App\TagFeature\Application\DTOResponse\TagResponseDTO;
use App\TagFeature\Domain\Entity\Tag;

final class TagDataMapper
{
    public function tagToResponse(Tag $tag, ?string $description = null): TagResponseDTO
    {
        return new TagResponseDTO(
            $tag->id()->value(),
            $tag->name()->value(),
            $tag->color()->value(),
            $tag->ownerId(),
            $tag->createdAt(),
            $description,
        );
    }
}
