<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Port;

interface ConfirmationCodeGeneratorInterface
{
    public function generate(): string;
}
