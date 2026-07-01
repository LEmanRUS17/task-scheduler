<?php

declare(strict_types=1);

namespace App\TagFeature\Presentation\Controller;

use App\TagFeatureApi\DTOResponse\TagResponseInterface;

final class TagView
{
    /** @return array<string, mixed> */
    public static function one(TagResponseInterface $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
            'ownerId' => $tag->getOwnerId(),
            'description' => $tag->getDescription(),
            'createdAt' => $tag->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param TagResponseInterface[] $tags
     * @return list<array<string, mixed>>
     */
    public static function many(array $tags): array
    {
        return array_values(array_map(static fn(TagResponseInterface $tag) => self::one($tag), $tags));
    }
}
