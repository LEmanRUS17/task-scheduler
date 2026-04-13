<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Port;

use App\UserFeature\Domain\ValueObject\HashedPassword;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): HashedPassword;
}
