<?php

declare(strict_types=1);

namespace App\NotificationFeature\Domain\Template;

use App\NotificationFeature\Domain\Exception\NotificationTemplateNotFoundException;

final class NotificationTemplateRenderer
{
    public function __construct(
        private readonly StandardNotificationTemplates $templates,
    ) {
    }

    /**
     * Substitutes the variable tags in the scenario's template for the channel.
     *
     * @param array<value-of<NotificationVariable>, string> $values keyed by tag, e.g. ['[TaskTitle]' => 'Fix bug']
     *
     * @throws NotificationTemplateNotFoundException when the scenario has no template for the channel
     * @throws \InvalidArgumentException when a variable the template uses has no value, or an
     *                                   unknown variable is passed
     */
    public function render(
        NotificationScenario $scenario,
        NotificationType $type,
        array $values = [],
    ): RenderedNotification
    {
        $template = $this->templates->get($scenario, $type);

        $expected = array_map(static fn (NotificationVariable $variable) => $variable->value, $template->variables);

        $missing = array_diff($expected, array_keys($values));
        if ($missing !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Missing notification variables for "%s": %s',
                $scenario->value,
                implode(', ', $missing),
            ));
        }

        $unknown = array_diff(array_keys($values), $expected);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown notification variables for "%s": %s',
                $scenario->value,
                implode(', ', $unknown),
            ));
        }

        return new RenderedNotification(
            subject: strtr($template->subject, $values),
            body: strtr($template->text, $values),
        );
    }
}
