<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTORequestFactory;

use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;

interface TeamCreateDTOFactoryInterface
{
    public function create(): TeamCreateRequestInterface;
}
