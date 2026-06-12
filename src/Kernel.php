<?php

declare(strict_types=1);

namespace App;

use App\AuditLogFeature\Infrastructure\Persistence\Doctrine\AuditLogMappingCompilerPass;
use App\DescriptionFeature\Infrastructure\Persistence\Doctrine\DescriptionMappingCompilerPass;
use App\ProfileFeature\Infrastructure\Persistence\Doctrine\ProfileMappingCompilerPass;
use App\SubscriptionFeature\Infrastructure\Persistence\Doctrine\SubscriptionMappingCompilerPass;
use App\TaskFeature\Infrastructure\Persistence\Doctrine\TaskMappingCompilerPass;
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
        $container->addCompilerPass(new AuditLogMappingCompilerPass());
        $container->addCompilerPass(new UserMappingCompilerPass());
        $container->addCompilerPass(new ProfileMappingCompilerPass());
        $container->addCompilerPass(new TeamMappingCompilerPass());
        $container->addCompilerPass(new WorkflowMappingCompilerPass());
        $container->addCompilerPass(new SubscriptionMappingCompilerPass());
        $container->addCompilerPass(new TaskMappingCompilerPass());
        $container->addCompilerPass(new DescriptionMappingCompilerPass());
    }
}
