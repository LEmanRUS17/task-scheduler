<?php

declare(strict_types=1);

namespace App\FileFeature\Domain\ValueObject;

enum FilePurpose: string
{
    case Avatar = 'avatar';
    case Attachment = 'attachment';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException("Unknown file purpose: {$value}");
    }
}
