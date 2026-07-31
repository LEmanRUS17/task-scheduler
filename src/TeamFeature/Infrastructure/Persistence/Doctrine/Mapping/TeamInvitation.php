<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\TeamFeature\Domain\ValueObject\TeamInvitationStatus;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TeamFeature\Domain\Entity\TeamInvitation> $metadata */
// @phpstan-ignore-next-line isset.variable
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('team_invitation');

$metadata->setIdentifier(['id']);

$builder->createField('id', 'string')
    ->columnName('id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('teamId', 'string', ['columnName' => 'team_id', 'length' => 36]);
$builder->addField('invitedUserId', 'string', ['columnName' => 'invited_user_id', 'length' => 36]);
$builder->addField('invitedByUserId', 'string', ['columnName' => 'invited_by_user_id', 'length' => 36]);
$builder->addField('role', 'string', ['enumType' => TeamMemberRole::class]);
$builder->addField('status', 'string', ['enumType' => TeamInvitationStatus::class]);
$builder->addField('token', 'string', ['length' => 64]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
$builder->addField('expiresAt', 'datetime_immutable', ['columnName' => 'expires_at']);
$builder->addField('respondedAt', 'datetime_immutable', ['columnName' => 'responded_at', 'nullable' => true]);
