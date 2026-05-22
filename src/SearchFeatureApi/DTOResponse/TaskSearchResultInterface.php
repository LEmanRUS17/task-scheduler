<?php

declare(strict_types=1);

namespace App\SearchFeatureApi\DTOResponse;

interface TaskSearchResultInterface
{
    public function getTaskId(): string;

    public function getTitle(): string;

    public function getStatus(): string;
}
