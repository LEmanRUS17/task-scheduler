<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Notification;

interface NotificationActionInterface
{
    public function getType(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
