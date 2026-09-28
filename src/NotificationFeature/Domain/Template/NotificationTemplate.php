<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

final class NotificationTemplate
{
    /**
     * @param list<NotificationVariable> $variables the variables the subject and text may use
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $text,
        public readonly array $variables = [],
    ) {
    }
}
