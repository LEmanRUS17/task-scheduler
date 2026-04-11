<?php

declare(strict_types=1);

namespace App\User\Application\Port;

use App\User\Domain\ValueObject\HashedPassword;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): HashedPassword;
}
