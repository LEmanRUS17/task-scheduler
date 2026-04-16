<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Repository;

use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\ValueObject\TeamId;

interface TeamRepositoryInterface
{
    public function save(Team $team): void;

    public function findById(TeamId $id): ?Team;

    /** @return Team[] */
    public function findAll(): array;

    public function delete(Team $team): void;
}
