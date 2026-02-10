<?php

namespace AwardWallet\MainBundle\Service\AirHelp;

use Psr\Container\ContainerInterface;

trait AirHelpImporterAwareTrait
{
    protected ?ContainerInterface $container;

    protected AirHelpImporter $airHelpImporter;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->airHelpImporter = $container->get(AirHelpImporter::class);
    }
}
