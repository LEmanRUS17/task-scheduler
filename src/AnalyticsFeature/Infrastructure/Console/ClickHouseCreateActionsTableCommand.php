<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Console;

use App\AnalyticsFeature\Infrastructure\ClickHouse\ClickHouseClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clickhouse:create-actions-table')]
final class ClickHouseCreateActionsTableCommand extends Command
{
    public function __construct(private readonly ClickHouseClient $client)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->client->query(<<<SQL
                CREATE TABLE IF NOT EXISTS task_actions (
                    task_id     String,
                    action      String,
                    actor_id    String,
                    metadata    String,
                    occurred_at DateTime
                ) ENGINE = MergeTree()
                ORDER BY (task_id, occurred_at)
                SQL);

            $output->writeln('[OK] Table created');

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $output->writeln('[ERROR] ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
