<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

final class UserId
{
    use StringValueObject;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Generates a random UUID v4 compliant with RFC 4122, section 4.4.
     *
     * Steps:
     *   1. random_bytes(16) — generates 128 random bits using the OS CSPRNG (/dev/urandom).
     *   2. $bytes[6] & 0x0f | 0x40 — clears the high nibble and sets bits 12–15 to 0100.
     *      This encodes version 4 (random) in the time_hi_and_version field.
     *   3. $bytes[8] & 0x3f | 0x80 — clears bits 6–7 and sets them to 10.
     *      This encodes the RFC 4122 variant in the clock_seq_hi_and_reserved field.
     *   4. bin2hex() + vsprintf() — formats the 32 hex characters into the canonical form:
     *      xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx, where y ∈ {8, 9, a, b}.
     */
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
