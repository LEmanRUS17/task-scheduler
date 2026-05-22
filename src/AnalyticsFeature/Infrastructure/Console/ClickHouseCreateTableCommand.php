<?php

declare(strict_types=1);

namespace App\AnalyticsFeature\Infrastructure\Console;

use App\AnalyticsFeature\Infrastructure\ClickHouse\ClickHouseClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clickhouse:create-table')]
final class ClickHouseCreateTableCommand extends Command
{
    public function __construct(private readonly ClickHouseClient $client)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->client->query(
                "CREATE TABLE IF NOT EXISTS task_events (
                    task_id     String,
                    team_id     String,
                    from_status String,
                    to_status   String,
                    occurred_at DateTime
                ) ENGINE = MergeTree()
                ORDER BY (team_id, occurred_at)"
            );
            $io->success('Table created');

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
