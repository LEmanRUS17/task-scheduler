<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('team_member');

$metadata->setIdentifier(['teamId', 'userId']);

$builder->createField('teamId', 'string')
    ->columnName('team_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->createField('userId', 'string')
    ->columnName('user_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('role', 'string', ['enumType' => TeamMemberRole::class]);
$builder->addField('joinedAt', 'datetime_immutable', ['columnName' => 'joined_at']);
