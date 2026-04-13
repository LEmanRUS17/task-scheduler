<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ProfileMappingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        DoctrineOrmMappingsPass::createPhpMappingDriver(
            [realpath(__DIR__ . '/Mapping') => 'App\ProfileFeature\Domain\Entity'],
        )->process($container);
    }
}
