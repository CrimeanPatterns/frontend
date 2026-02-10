<?php

namespace AwardWallet\MainBundle\DependencyInjection\CallbackTaskAutowirePass;

use Psr\Container\ContainerInterface;

class CallbackTaskServiceLocatorHolder
{
    private ContainerInterface $locator;
    private array $parametersMap = [];

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getLocator(): ContainerInterface
    {
        return $this->locator;
    }

    public function getParametersMap(): array
    {
        return $this->parametersMap;
    }

    public function setParametersMap(array $parametersMap): void
    {
        $this->parametersMap = $parametersMap;
    }
}
