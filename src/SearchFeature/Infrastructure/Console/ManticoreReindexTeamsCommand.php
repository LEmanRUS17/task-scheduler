<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Domain\Port\TeamSearchIndexInterface;
use App\SearchFeature\Infrastructure\Indexing\TeamOwnerResolver;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:reindex-teams', description: 'Reindex all teams in Manticore')]
final class ManticoreReindexTeamsCommand extends Command
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly TeamSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $teams = $this->teamService->getList();
        $count = count($teams);

        $io->progressStart($count);

        foreach ($teams as $team) {
            $members = $this->teamService->getMembers($team->getId());

            $memberIds = array_values(array_map(
                static fn($member) => $member->getUserId(),
                $members,
            ));

            $this->searchIndex->index(
                $team->getId(),
                $team->getTitle(),
                $team->getStatus(),
                TeamOwnerResolver::resolve($members),
                $team->getCreatedAt(),
                $memberIds,
            );
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("Reindexed {$count} teams.");

        return Command::SUCCESS;
    }
}
