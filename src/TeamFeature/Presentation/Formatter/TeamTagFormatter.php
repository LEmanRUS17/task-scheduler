<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Formatter;

use App\TagFeature\Application\DTOResponse\TagResponseDTO;

final class TeamTagFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function format(TagResponseDTO $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
        ];
    }
}
