<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:create-user-index')]
final class ManticoreCreateUserIndexCommand extends Command
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
                "CREATE TABLE IF NOT EXISTS users (user_id string, username text, email text, firstname text, lastname text, midlname text, team_ids text) min_infix_len='2'"
            );
            $io->success('Index created');

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
