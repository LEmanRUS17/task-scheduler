<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\SearchFeature\Domain\Port\UserSearchIndexInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:reindex-users', description: 'Reindex all users in Manticore')]
final class ManticoreReindexUsersCommand extends Command
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
        private readonly UserServiceInterface $userService,
        private readonly TeamServiceInterface $teamService,
        private readonly UserSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userIds = $this->profileService->getAllUserIds();
        $count = count($userIds);

        $io->progressStart($count);

        foreach ($userIds as $userId) {
            $user = $this->userService->findById($userId);

            if ($user === null) {
                $io->progressAdvance();
                continue;
            }

            $profile = $this->profileService->getByUserId($userId);

            $teamIds = array_values(array_map(
                static fn($team) => $team->getId(),
                $this->teamService->getTeamsByUserId($userId),
            ));

            $this->searchIndex->index(
                $userId,
                $profile->getUsername() ?? '',
                $user->getEmail(),
                $profile->getFirstname() ?? '',
                $profile->getLastname() ?? '',
                $profile->getMidlname() ?? '',
                $teamIds,
            );
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("Reindexed {$count} users.");

        return Command::SUCCESS;
    }
}
