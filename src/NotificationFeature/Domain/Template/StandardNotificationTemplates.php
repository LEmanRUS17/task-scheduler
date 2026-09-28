<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

use App\NotificationFeature\Domain\Exception\NotificationTemplateNotFoundException;
use App\NotificationFeature\Domain\Template\NotificationScenario as Scenario;
use App\NotificationFeature\Domain\Template\NotificationType as Type;
use App\NotificationFeature\Domain\Template\NotificationVariable as Variable;

/**
 * The built-in text of every notification, per scenario and channel.
 */
final class StandardNotificationTemplates
{
    /**
     * @throws NotificationTemplateNotFoundException when the scenario has no template for the channel
     */
    public function get(Scenario $scenario, Type $type): NotificationTemplate
    {
        return match ([$scenario, $type]) {
            [Scenario::TaskCreated, Type::Email] => new NotificationTemplate(
                subject: 'Task "[TaskTitle]" created',
                text: 'Your task "[TaskTitle]" has been successfully created.',
                variables: [Variable::TaskTitle],
            ),
            [Scenario::TaskStatusChanged, Type::Email],
            [Scenario::TaskStatusChanged, Type::Push] => new NotificationTemplate(
                subject: 'Task "[TaskTitle]" status changed',
                text: 'Task "[TaskTitle]" has been moved from "[FromStatus]" to "[ToStatus]".',
                variables: [Variable::TaskTitle, Variable::FromStatus, Variable::ToStatus],
            ),
            [Scenario::TaskStatusChanged, Type::Messenger] => new NotificationTemplate(
                subject: 'Task "[TaskTitle]" status changed',
                text: "Task \"[TaskTitle]\" status changed\n\n"
                    . 'Task "[TaskTitle]" has been moved from "[FromStatus]" to "[ToStatus]".',
                variables: [Variable::TaskTitle, Variable::FromStatus, Variable::ToStatus],
            ),
            [Scenario::TaskCompleted, Type::Email],
            [Scenario::TaskCompleted, Type::Push] => new NotificationTemplate(
                subject: 'Task "[TaskTitle]" completed',
                text: 'Task "[TaskTitle]" has been completed.',
                variables: [Variable::TaskTitle],
            ),
            [Scenario::TaskCompleted, Type::Messenger] => new NotificationTemplate(
                subject: 'Task "[TaskTitle]" completed',
                text: "Task \"[TaskTitle]\" completed\n\nTask \"[TaskTitle]\" has been completed.",
                variables: [Variable::TaskTitle],
            ),
            [Scenario::TaskAssigneeAdded, Type::Email] => new NotificationTemplate(
                subject: 'You have been assigned to task "[TaskTitle]"',
                text: 'You have been assigned to task "[TaskTitle]".',
                variables: [Variable::TaskTitle],
            ),
            [Scenario::TeamMemberInvited, Type::Email] => new NotificationTemplate(
                subject: 'You have been invited to join "[TeamTitle]"',
                text: "You have been invited to join the team \"[TeamTitle]\" on Task Scheduler.\n\n"
                    . "Accept the invitation: [InvitationLink]\n\n"
                    . "Or use this invitation code manually: [InvitationCode]\n\n"
                    . "The invitation is valid for 7 days.\n\n"
                    . 'If you did not expect this invitation, you can safely ignore this email.',
                variables: [Variable::TeamTitle, Variable::InvitationLink, Variable::InvitationCode],
            ),
            [Scenario::UserRegistered, Type::Email] => new NotificationTemplate(
                subject: 'Confirm your registration',
                text: "Welcome to Task Scheduler!\n\n"
                    . "Use the following code to complete your registration: [ConfirmationCode]\n\n"
                    . 'The code is valid for 24 hours.',
                variables: [Variable::ConfirmationCode],
            ),
            [Scenario::PasswordResetRequested, Type::Email] => new NotificationTemplate(
                subject: 'Reset your password',
                text: "We received a request to reset your Task Scheduler password.\n\n"
                    . "Use the following code to choose a new password: [ResetCode]\n\n"
                    . "The code is valid for 1 hour.\n\n"
                    . 'If you did not request this, you can safely ignore this email.',
                variables: [Variable::ResetCode],
            ),
            default => throw NotificationTemplateNotFoundException::for($scenario, $type),
        };
    }
}
