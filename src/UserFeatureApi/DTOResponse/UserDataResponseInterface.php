<?php

declare(strict_types=1);

namespace App\UserFeatureApi\DTOResponse;

interface UserDataResponseInterface
{
    public function getId(): string;

    public function getEmail(): string;
}
