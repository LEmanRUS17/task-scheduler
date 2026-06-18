<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\DTOResponse;

interface TeamSearchResultInterface
{
    public function getTeamId(): string;

    public function getTitle(): string;

    public function getStatus(): string;
}
