<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\NotificationFeature\Domain\Entity\TelegramChat> $metadata */
// @phpstan-ignore-next-line isset.variable
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('telegram_chat');

$builder->createField('userId', 'string')
    ->columnName('user_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('chatId', 'bigint', ['columnName' => 'chat_id']);
$builder->addField('state', 'integer', ['options' => ['default' => 0]]);
