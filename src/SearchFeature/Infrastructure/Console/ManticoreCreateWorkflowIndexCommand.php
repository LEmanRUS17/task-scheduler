<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:create-workflow-index')]
final class ManticoreCreateWorkflowIndexCommand extends Command
{
    public function __construct(private readonly ManticoreClient $manticoreClient)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->manticoreClient->sql(
                "CREATE TABLE IF NOT EXISTS workflows (workflow_id string, title text, description text, created_by string, created_at timestamp) min_infix_len='2'"
            );
            $io->success('Index created');

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
