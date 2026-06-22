<?php

declare(strict_types=1);

namespace App\FileFeatureApi\Contract;

/**
 * Avatar render sizes. Values are pixel dimensions of the square image and are
 * sized for the highest pixel density we target (×3), so the same asset stays
 * crisp on 1x/2x/3x screens when scaled down via CSS:
 *
 *   Small  72px  → 24 CSS-px @3x  (inline lists, mentions)
 *   Medium 144px → 48 CSS-px @3x  (next to a nickname, cards)
 *   Large  384px → 128 CSS-px @3x (large profile avatar)
 */
enum ImageSize: int
{
    case Small = 72;
    case Medium = 144;
    case Large = 384;

    /** Methods returning an avatar default to the largest size. */
    public static function default(): self
    {
        return self::Large;
    }

    public static function fromName(?string $name): self
    {
        return match (strtolower((string) $name)) {
            'small' => self::Small,
            'medium' => self::Medium,
            'large' => self::Large,
            default => self::default(),
        };
    }

    public function pixels(): int
    {
        return $this->value;
    }

    public function fileName(): string
    {
        return $this->value . '.webp';
    }
}
