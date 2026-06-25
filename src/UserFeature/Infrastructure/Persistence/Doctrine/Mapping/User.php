<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\UserFeature\Domain\ValueObject\UserStatus;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\UserFeature\Domain\Entity\User> $metadata */
// @phpstan-ignore-next-line isset.variable
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('user');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('email', 'string', ['length' => 180, 'unique' => true]);
$builder->addField('password', 'string');
$builder->addField('status', 'string', ['enumType' => UserStatus::class]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
$builder->addField('deletedAt', 'datetime_immutable', ['columnName' => 'deleted_at', 'nullable' => true]);
$builder->addField('passwordUpdatedAt', 'datetime_immutable', ['columnName' => 'password_updated_at', 'nullable' => true]);
$builder->addField('confirmationCode', 'string', ['columnName' => 'confirmation_code', 'nullable' => true]);
$builder->addField('codeExpiresAt', 'datetime_immutable', ['columnName' => 'code_expires_at', 'nullable' => true]);
$builder->addField('passwordResetCode', 'string', ['columnName' => 'password_reset_code', 'nullable' => true]);
$builder->addField('passwordResetExpiresAt', 'datetime_immutable', [
    'columnName' => 'password_reset_expires_at',
    'nullable' => true,
]);
$builder->addField('roles', 'json');
