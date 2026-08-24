<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817100921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill default workflow (открыт/закрыт) for existing users without one';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            WITH new_workflow AS (
                INSERT INTO workflow (id, title, created_by, created_at, is_default)
                SELECT gen_random_uuid(), 'Базовый', u.id, now(), TRUE
                FROM "user" u
                WHERE u.status != 'deleted'
                  AND u.deleted_at IS NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM workflow w WHERE w.created_by = u.id AND w.is_default = TRUE
                  )
                RETURNING id AS workflow_id
            ),
            new_statuses AS (
                INSERT INTO workflow_status (id, workflow_id, label, is_initial, is_final, created_at)
                SELECT gen_random_uuid(), nw.workflow_id, v.label, v.is_initial, v.is_final, now()
                FROM new_workflow nw
                CROSS JOIN (VALUES ('открыт', TRUE, FALSE), ('закрыт', FALSE, TRUE)) AS v(label, is_initial, is_final)
                RETURNING id, workflow_id, label
            )
            INSERT INTO workflow_transition (id, workflow_id, name, from_status_id, to_status_id, created_at)
            SELECT gen_random_uuid(), o.workflow_id, 'закрыть', o.id, c.id, now()
            FROM new_statuses o
            JOIN new_statuses c ON c.workflow_id = o.workflow_id AND c.label = 'закрыт'
            WHERE o.label = 'открыт'
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Only reverts workflows matching the exact default shape this migration creates; any
        // default workflow later modified by a user (renamed, extra statuses, etc.) is left intact.
        $this->addSql(<<<'SQL'
            DELETE FROM workflow_transition
            WHERE workflow_id IN (
                SELECT id FROM workflow WHERE is_default = TRUE AND title = 'Базовый'
            )
            AND name = 'закрыть'
            SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM workflow_status
            WHERE workflow_id IN (
                SELECT id FROM workflow WHERE is_default = TRUE AND title = 'Базовый'
            )
            AND label IN ('открыт', 'закрыт')
            SQL);

        $this->addSql("DELETE FROM workflow WHERE is_default = TRUE AND title = 'Базовый'");
    }
}
