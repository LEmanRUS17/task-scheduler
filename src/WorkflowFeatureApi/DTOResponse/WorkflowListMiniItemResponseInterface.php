<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\DTOResponse;

interface WorkflowListMiniItemResponseInterface
{
    public function getId(): string;

    public function getName(): string;
}
