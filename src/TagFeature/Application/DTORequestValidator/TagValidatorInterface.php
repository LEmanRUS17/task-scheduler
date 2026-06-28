<?php

declare(strict_types=1);

namespace App\TagFeature\Application\DTORequestValidator;

interface TagValidatorInterface
{
    /** @return array<string, list<string>> */
    public function validate(object $dto): array;
}
