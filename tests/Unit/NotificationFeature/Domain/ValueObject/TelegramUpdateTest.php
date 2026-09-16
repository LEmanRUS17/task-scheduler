<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Domain\ValueObject;

use App\NotificationFeature\Domain\ValueObject\TelegramUpdate;
use PHPUnit\Framework\TestCase;

final class TelegramUpdateTest extends TestCase
{
    public function testReadsChatIdAndTextFromAMessageUpdate(): void
    {
        $payload = [
            'update_id' => 1,
            'message' => [
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'text' => '/start abc',
            ],
        ];

        $update = TelegramUpdate::fromWebhookPayload($payload);

        $this->assertNotNull($update);
        $this->assertSame(123456789, $update->chatId);
        $this->assertSame('/start abc', $update->text);
    }

    public function testReadsChatIdFromACallbackQueryUpdate(): void
    {
        $payload = [
            'update_id' => 1,
            'callback_query' => [
                'id' => '1',
                'message' => [
                    'chat' => ['id' => 42, 'type' => 'private'],
                ],
                'data' => 'confirm_action',
            ],
        ];

        $update = TelegramUpdate::fromWebhookPayload($payload);

        $this->assertNotNull($update);
        $this->assertSame(42, $update->chatId);
        $this->assertNull($update->text);
    }

    public function testReturnsNullWhenThereIsNoChatId(): void
    {
        $this->assertNull(TelegramUpdate::fromWebhookPayload(['update_id' => 1]));
    }
}
