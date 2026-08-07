<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Message;

final class TeamMemberInvitedMessage
{
    public function __construct(
        public readonly string $teamId,
        public readonly string $teamTitle,
        public readonly string $invitedEmail,
        public readonly string $token,
    ) {
    }
}
