<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Console;

use App\NotificationFeature\Domain\Notification\TelegramNotifierInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:telegram:notify', description: 'Send a test notification to the Telegram bot via RabbitMQ')]
final class SendTelegramNotificationCommand extends Command
{
    public function __construct(private readonly TelegramNotifierInterface $telegramNotifier)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('chatId', InputArgument::REQUIRED, 'Telegram chat ID')
            ->addArgument('text', InputArgument::REQUIRED, 'Message text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chatId = (int) $input->getArgument('chatId');
        $text = (string) $input->getArgument('text');

        $this->telegramNotifier->notify($chatId, $text);

        $io->success("Notification dispatched to chat {$chatId}.");

        return Command::SUCCESS;
    }
}
