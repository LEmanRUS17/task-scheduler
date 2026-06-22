<?php

declare(strict_types=1);

namespace App\UserFeatureApi\DTORequest;

interface ConfirmUserRequestInterface
{
    public function getEmail(): string;

    public function getCode(): string;
}
