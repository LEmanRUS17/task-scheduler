<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\UserFeature\Domain\ValueObject\UserStatus;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
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
$builder->addField('roles', 'json');
