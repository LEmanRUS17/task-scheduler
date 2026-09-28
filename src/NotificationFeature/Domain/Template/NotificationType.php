<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

/**
 * The delivery channel a template is written for. Each channel gets its own text because
 * e.g. a messenger has no separate subject line.
 */
enum NotificationType: int
{
    case Email = 1;
    case Push = 3;
    case Messenger = 4;
}
