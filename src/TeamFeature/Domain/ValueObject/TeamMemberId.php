<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\ValueObject;

final class TeamMemberId
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)));
    }

    public static function fromString(string $value): self
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException("Invalid UUID v4: \"{$value}\"");
        }

        return new self($value);
    }
}
