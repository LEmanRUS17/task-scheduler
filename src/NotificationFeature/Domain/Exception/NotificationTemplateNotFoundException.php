<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Exception;

use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationType;

final class NotificationTemplateNotFoundException extends \DomainException
{
    public static function for(NotificationScenario $scenario, NotificationType $type): self
    {
        return new self(sprintf('No "%s" notification template for "%s"', $type->name, $scenario->value));
    }
}
