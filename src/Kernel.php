<?php

declare(strict_types=1);

namespace App;

use App\ProfileFeature\Infrastructure\Persistence\Doctrine\ProfileMappingCompilerPass;
use App\TeamFeature\Infrastructure\Persistence\Doctrine\TeamMappingCompilerPass;
use App\WorkflowFeature\Infrastructure\Persistence\Doctrine\WorkflowMappingCompilerPass;
use App\UserFeature\Infrastructure\Persistence\Doctrine\UserMappingCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new UserMappingCompilerPass());
        $container->addCompilerPass(new ProfileMappingCompilerPass());
        $container->addCompilerPass(new TeamMappingCompilerPass());
        $container->addCompilerPass(new WorkflowMappingCompilerPass());
    }
}
