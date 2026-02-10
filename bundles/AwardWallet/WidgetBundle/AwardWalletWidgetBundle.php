<?php

namespace AwardWallet\WidgetBundle;

use AwardWallet\WidgetBundle\DependencyInjection\Compiler\WidgetContainerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AwardWalletWidgetBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new WidgetContainerPass());
    }
}
