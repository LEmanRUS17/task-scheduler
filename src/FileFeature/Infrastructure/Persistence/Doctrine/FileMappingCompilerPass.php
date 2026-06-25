<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FileMappingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        DoctrineOrmMappingsPass::createPhpMappingDriver(
            [realpath(__DIR__ . '/Mapping') => 'App\FileFeature\Domain\Entity'],
        )->process($container);
    }
}
