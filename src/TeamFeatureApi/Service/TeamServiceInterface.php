<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\Service;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface as ResponseDTO;

interface TeamServiceInterface
{
    /** @return ResponseDTO[] */
    public function getList(): array;

    public function getById(string $id): ?ResponseDTO;

    public function create(TeamCreateRequestInterface $dtoRequest): ResponseDTO;

    public function deleteById(string $id): void;
}
