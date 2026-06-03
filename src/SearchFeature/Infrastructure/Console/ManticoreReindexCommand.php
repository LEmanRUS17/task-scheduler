<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Domain\Port\TaskSearchIndexInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:reindex', description: 'Reindex all tasks in Manticore')]
final class ManticoreReindexCommand extends Command
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly TaskSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tasks = $this->taskService->getAll();
        $count = count($tasks);

        $io->progressStart($count);

        foreach ($tasks as $task) {
            $this->searchIndex->index(
                $task->getId(),
                $task->getTitle(),
                $task->getPriority(),
                $task->getStatus(),
                $task->getTeamId(),
                $task->getCreatedBy(),
            );
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("Reindexed {$count} tasks.");

        return Command::SUCCESS;
    }
}
