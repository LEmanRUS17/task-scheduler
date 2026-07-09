<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DTORequestValidator;

interface CommentValidatorInterface
{
    /** @return array<string, list<string>> */
    public function validate(object $dto): array;
}
