<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\DTOResponse;

interface TeamTagDataResponseInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getColor(): string;
}
