<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Notification;

final class ScriptAction implements NotificationActionInterface
{
    public const string TYPE = 'script';

    /**
     * @param string $script 
     * @param array $args 
     */
    public function __construct(
        public readonly string $script,
        public readonly array $args = [],
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'script' => $this->script,
            'args' => $this->args,
        ];
    }
}
