<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Formatter;

use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;

final class TeamResponseFormatter
{
    /**
     * @param list<TagResponseInterface> $tags
     * @return array<string, mixed>
     */
    public static function format(TeamDataResponseInterface $team, array $tags = []): array
    {
        return [
            'tags' => array_map(
                static fn(TagResponseInterface $tag): array => [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'color' => $tag->getColor(),
                ],
                $tags,
            ),
            'id' => $team->getId(),
            'title' => $team->getTitle(),
            'status' => $team->getStatus(),
            'createdAt' => $team->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
