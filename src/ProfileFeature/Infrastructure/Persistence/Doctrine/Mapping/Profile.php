<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('profile');

$builder->createField('userId', 'string')
    ->columnName('user_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('username', 'string', ['nullable' => true]);
$builder->addField('firstname', 'string', ['nullable' => true]);
$builder->addField('lastname', 'string', ['nullable' => true]);
$builder->addField('midlname', 'string', ['nullable' => true]);
$builder->addField('status', 'string', ['nullable' => true]);
$builder->addField('lastLogin', 'datetime_immutable', ['columnName' => 'last_login', 'nullable' => true]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
