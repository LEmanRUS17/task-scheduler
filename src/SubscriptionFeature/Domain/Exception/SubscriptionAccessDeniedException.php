<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Exception;

final class SubscriptionAccessDeniedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Access denied');
    }
}
