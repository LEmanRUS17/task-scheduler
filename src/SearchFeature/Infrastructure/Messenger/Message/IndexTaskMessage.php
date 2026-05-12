<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Message;

final class IndexTaskMessage
{
    public function __construct(public readonly string $taskId) {}
}
