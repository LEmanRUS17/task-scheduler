<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\Template;

use App\NotificationFeature\Domain\Template\NotificationScenario;
use App\NotificationFeature\Domain\Template\NotificationType;
use App\NotificationFeature\Domain\Template\StandardNotificationTemplates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StandardNotificationTemplatesTest extends TestCase
{
    /** @return iterable<string, array{NotificationScenario, NotificationType}> */
    public static function templates(): iterable
    {
        foreach (NotificationScenario::cases() as $scenario) {
            yield $scenario->value . ' email' => [$scenario, NotificationType::Email];
        }

        foreach ([NotificationScenario::TaskStatusChanged, NotificationScenario::TaskCompleted] as $scenario) {
            yield $scenario->value . ' push' => [$scenario, NotificationType::Push];
            yield $scenario->value . ' messenger' => [$scenario, NotificationType::Messenger];
        }
    }

    /**
     * Every tag in a template must be declared in its variables, and every declared variable
     * must be used — otherwise the renderer would leave a raw tag in the text or demand a
     * value nobody reads.
     */
    #[DataProvider('templates')]
    public function testTemplateUsesExactlyTheVariablesItDeclares(
        NotificationScenario $scenario,
        NotificationType $type,
    ): void
    {
        $template = (new StandardNotificationTemplates())->get($scenario, $type);

        preg_match_all('/\[[A-Za-z]+\]/', $template->subject . $template->text, $matches);
        $used = array_values(array_unique($matches[0]));
        $declared = array_map(static fn ($variable) => $variable->value, $template->variables);

        sort($used);
        sort($declared);

        $this->assertSame($declared, $used);
    }
}
