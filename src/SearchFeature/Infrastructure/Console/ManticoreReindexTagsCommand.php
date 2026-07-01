<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Console;

use App\SearchFeature\Domain\Port\TagSearchIndexInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:manticore:reindex-tags', description: 'Reindex all tags in Manticore')]
final class ManticoreReindexTagsCommand extends Command
{
    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tags = $this->tagService->getList();
        $count = count($tags);

        $io->progressStart($count);

        foreach ($tags as $tag) {
            $this->searchIndex->index(
                $tag->getId(),
                $tag->getName(),
                $tag->getDescription() ?? '',
                $tag->getOwnerId(),
                $tag->getCreatedAt(),
            );
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success("Reindexed {$count} tags.");

        return Command::SUCCESS;
    }
}
