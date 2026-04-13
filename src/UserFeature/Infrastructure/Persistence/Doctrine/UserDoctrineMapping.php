<?php

declare(strict_types=1);

namespace App\UserFeature\Infrastructure\Persistence\Doctrine;

use App\UserFeature\Domain\Entity\User;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

final class UserDoctrineMapping
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        if ($event->getClassMetadata()->getName() !== User::class) {
            return;
        }

        $event->getClassMetadata()->setPrimaryTable(['name' => '`user`']);
    }
}
