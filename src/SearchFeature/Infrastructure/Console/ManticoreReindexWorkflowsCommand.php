<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Domain\Port\WorkflowSearchIndexInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:reindex-workflows', description: 'Reindex all workflows in Manticore')]
final class ManticoreReindexWorkflowsCommand extends Command
{
    public function __construct(
        private readonly WorkflowServiceInterface $workflowService,
        private readonly WorkflowSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $workflows = $this->workflowService->getList();
        $count = count($workflows);

        $io->progressStart($count);

        foreach ($workflows as $workflow) {
            $this->searchIndex->index(
                $workflow->getId(),
                $workflow->getTitle(),
                $workflow->getDescription() ?? '',
                $workflow->getCreatedBy(),
                $workflow->getCreatedAt(),
            );
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("Reindexed {$count} workflows.");

        return Command::SUCCESS;
    }
}
