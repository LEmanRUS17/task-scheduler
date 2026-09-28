<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

final class RenderedNotification
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
    ) {
    }
}
