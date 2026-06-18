<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\DTOResponse;

use App\SearchFeatureApi\DTOResponse\TeamSearchResultInterface;

final class TeamSearchResult implements TeamSearchResultInterface
{
    public function __construct(
        private readonly string $teamId,
        private readonly string $title,
        private readonly string $status,
    ) {
    }

    public function getTeamId(): string
    {
        return $this->teamId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
