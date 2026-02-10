<?php

namespace AwardWallet\MainBundle;

use AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass\CallbackTaskAutowirePass;
use AwardWallet\MainBundle\DependencyInjection\CatchNonAutowiredServicesCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\ClassOverrideCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\DoctrineEntityListenerPass;
use AwardWallet\MainBundle\DependencyInjection\DoctrineJsonCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\LazyCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\NoDICompilerPass;
use AwardWallet\MainBundle\DependencyInjection\PublicServiceCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\PublicTestAliasesCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\ServiceOverrideCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\SqlLoggerPass;
use AwardWallet\MainBundle\DependencyInjection\TaggedServicesCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\TimelineFormatterCompilerPass;
use AwardWallet\MainBundle\DependencyInjection\WellKnownAssociationsCompilerPass;
use AwardWallet\Manager\SchemaCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AwardWalletMainBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DoctrineEntityListenerPass());
        $container->addCompilerPass(new TaggedServicesCompilerPass());
        $container->addCompilerPass(new SqlLoggerPass());
        $container->addCompilerPass(new TimelineFormatterCompilerPass());
        $container->addCompilerPass(new ClassOverrideCompilerPass());
        $container->addCompilerPass(new ServiceOverrideCompilerPass());
        $container->addCompilerPass(new NoDICompilerPass());
        $container->addCompilerPass(new CallbackTaskAutowirePass());
        $container->addCompilerPass(new LazyCompilerPass());
        $container->addCompilerPass(new DoctrineJsonCompilerPass());
        $container->addCompilerPass(new WellKnownAssociationsCompilerPass());
        $container->addCompilerPass(new SchemaCompilerPass());
        $container->addCompilerPass(new PublicServiceCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new PublicTestAliasesCompilerPass());
        $container->addCompilerPass(new CatchNonAutowiredServicesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING, -100);
    }
}
