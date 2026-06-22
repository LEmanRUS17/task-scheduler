<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Confirmation;

use App\UserFeature\Domain\Port\ConfirmationCodeGeneratorInterface;

final class NumericConfirmationCodeGenerator implements ConfirmationCodeGeneratorInterface
{
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
