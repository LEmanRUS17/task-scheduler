<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

/**
 * A placeholder that can appear in a template's subject or text. The value is the tag
 * written in the template; title() is its human-readable name.
 */
enum NotificationVariable: string
{
    case TaskTitle = '[TaskTitle]';
    case FromStatus = '[FromStatus]';
    case ToStatus = '[ToStatus]';
    case TeamTitle = '[TeamTitle]';
    case InvitationLink = '[InvitationLink]';
    case InvitationCode = '[InvitationCode]';
    case ConfirmationCode = '[ConfirmationCode]';
    case ResetCode = '[ResetCode]';

    public function title(): string
    {
        return match ($this) {
            self::TaskTitle => 'Task title',
            self::FromStatus => 'Previous status',
            self::ToStatus => 'New status',
            self::TeamTitle => 'Team title',
            self::InvitationLink => 'Invitation link',
            self::InvitationCode => 'Invitation code',
            self::ConfirmationCode => 'Registration confirmation code',
            self::ResetCode => 'Password reset code',
        };
    }
}
