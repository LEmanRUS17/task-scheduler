<?php

declare(strict_types=1);

namespace App\TaskFeatureApi\Service;

use App\TaskFeatureApi\DTORequest\TaskCreateRequestInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface as ResponseDTO;

interface TaskServiceInterface
{
    /** @return ResponseDTO[] */
    public function getList(): array;

    public function getById(string $id): ?ResponseDTO;

    public function create(
        TaskCreateRequestInterface $dtoRequest,
        string $creatorUserId
    ): ResponseDTO;

    public function deleteById(string $id): void;
}
