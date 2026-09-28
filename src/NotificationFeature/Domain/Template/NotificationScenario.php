<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

/**
 * The event a notification is sent for. Values double as the `event` of NotificationDispatchMessage.
 */
enum NotificationScenario: string
{
    case TaskCreated = 'task.created';
    case TaskStatusChanged = 'task.status_changed';
    case TaskCompleted = 'task.completed';
    case TaskAssigneeAdded = 'task.assignee_added';
    case TeamMemberInvited = 'team.member_invited';
    case UserRegistered = 'user.registered';
    case PasswordResetRequested = 'user.password_reset_requested';
}
