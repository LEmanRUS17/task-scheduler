<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Template;

use App\NotificationFeature\Domain\Exception\NotificationTemplateNotFoundException;
use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationTemplateRenderer;
use App\NotificationFeature\Domain\Template\NotificationType;
use App\NotificationFeature\Domain\Template\NotificationVariable;
use App\NotificationFeature\Domain\Template\StandardNotificationTemplates;
use PHPUnit\Framework\TestCase;

final class NotificationTemplateRendererTest extends TestCase
{
    private NotificationTemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new NotificationTemplateRenderer(new StandardNotificationTemplates());
    }

    public function testRenderSubstitutesVariablesInSubjectAndBody(): void
    {
        $notification = $this->renderer->render(NotificationScenario::TaskStatusChanged, NotificationType::Email, [
            NotificationVariable::TaskTitle->value => 'Fix login bug',
            NotificationVariable::FromStatus->value => 'In Progress',
            NotificationVariable::ToStatus->value => 'Review',
        ]);

        $this->assertSame('Task "Fix login bug" status changed', $notification->subject);
        $this->assertSame('Task "Fix login bug" has been moved from "In Progress" to "Review".', $notification->body);
    }

    public function testRenderUsesTheTemplateOfTheRequestedChannel(): void
    {
        $values = [NotificationVariable::TaskTitle->value => 'Fix login bug'];

        $email = $this->renderer->render(NotificationScenario::TaskCompleted, NotificationType::Email, $values);
        $messenger = $this->renderer->render(NotificationScenario::TaskCompleted, NotificationType::Messenger, $values);

        $this->assertSame('Task "Fix login bug" has been completed.', $email->body);
        $this->assertSame(
            "Task \"Fix login bug\" completed\n\nTask \"Fix login bug\" has been completed.",
            $messenger->body,
        );
    }

    public function testRenderDoesNotSubstituteTagsInsideValues(): void
    {
        $notification = $this->renderer->render(NotificationScenario::TaskCreated, NotificationType::Email, [
            NotificationVariable::TaskTitle->value => 'Rename [TaskTitle] tag',
        ]);

        $this->assertSame('Task "Rename [TaskTitle] tag" created', $notification->subject);
    }

    public function testRenderThrowsWhenAVariableIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[ToStatus]');

        $this->renderer->render(NotificationScenario::TaskStatusChanged, NotificationType::Email, [
            NotificationVariable::TaskTitle->value => 'Fix login bug',
            NotificationVariable::FromStatus->value => 'In Progress',
        ]);
    }

    public function testRenderThrowsWhenAnUnknownVariableIsPassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[ResetCode]');

        $this->renderer->render(NotificationScenario::TaskCreated, NotificationType::Email, [
            NotificationVariable::TaskTitle->value => 'Fix login bug',
            NotificationVariable::ResetCode->value => '123456',
        ]);
    }

    public function testRenderThrowsWhenTheScenarioHasNoTemplateForTheChannel(): void
    {
        $this->expectException(NotificationTemplateNotFoundException::class);

        $this->renderer->render(NotificationScenario::UserRegistered, NotificationType::Messenger, [
            NotificationVariable::ConfirmationCode->value => '123456',
        ]);
    }
}
