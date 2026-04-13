<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\EventListener;

use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\UserFeature\Domain\Event\UserRegistered;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class UserRegisteredListener
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
    ) {}

    public function __invoke(UserRegistered $event): void
    {
        $this->profileService->createForUser($event->userId->value());
    }
}
