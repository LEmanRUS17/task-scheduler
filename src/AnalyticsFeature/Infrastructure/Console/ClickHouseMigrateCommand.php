<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Console;

use App\AnalyticsFeature\Infrastructure\ClickHouse\ClickHouseClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clickhouse:migrate', description: 'Apply all ClickHouse table migrations')]
final class ClickHouseMigrateCommand extends Command
{
    /** Add new ClickHouse DDL here; every statement must be idempotent (CREATE TABLE IF NOT EXISTS). */
    private const SCHEMA = [
        'task_events' => "CREATE TABLE IF NOT EXISTS task_events (
            task_id     String,
            team_id     String,
            from_status String,
            to_status   String,
            occurred_at DateTime
        ) ENGINE = MergeTree()
        ORDER BY (team_id, occurred_at)",

        'task_actions' => "CREATE TABLE IF NOT EXISTS task_actions (
            task_id     String,
            action      String,
            actor_id    String,
            metadata    String,
            occurred_at DateTime
        ) ENGINE = MergeTree()
        ORDER BY (task_id, occurred_at)",
    ];

    public function __construct(private readonly ClickHouseClient $client)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (self::SCHEMA as $table => $ddl) {
            try {
                $this->client->query($ddl);
                $io->writeln("[OK] {$table}");
            } catch (\RuntimeException $e) {
                $io->error("Failed to migrate \"{$table}\": {$e->getMessage()}");

                return Command::FAILURE;
            }
        }

        $io->success('All ClickHouse migrations applied.');

        return Command::SUCCESS;
    }
}
