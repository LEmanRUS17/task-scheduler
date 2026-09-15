<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Console;

use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:telegram:notify-user', description: 'Send a Telegram notification to the chat linked to a user')]
final class SendTelegramNotificationToUserCommand extends Command
{
    public function __construct(
        private readonly TelegramChatRepositoryInterface $telegramChats,
        private readonly TelegramNotifierInterface $telegramNotifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('text', InputArgument::REQUIRED, 'Message text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = (string) $input->getArgument('userId');
        $text = (string) $input->getArgument('text');

        $telegramChat = $this->telegramChats->findByUserId($userId);

        if ($telegramChat === null) {
            $io->error("No Telegram chat linked to user {$userId}.");

            return Command::FAILURE;
        }

        $this->telegramNotifier->notify($telegramChat->chatId(), $text);

        $io->success("Notification dispatched to chat {$telegramChat->chatId()} (user {$userId}).");

        return Command::SUCCESS;
    }
}
