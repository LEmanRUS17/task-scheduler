<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NotificationMappingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        DoctrineOrmMappingsPass::createPhpMappingDriver(
            [realpath(__DIR__ . '/Mapping') => 'App\NotificationFeature\Domain\Entity'],
        )->process($container);
    }
}
