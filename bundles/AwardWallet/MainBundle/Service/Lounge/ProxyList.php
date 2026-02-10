<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Psr\Container\ContainerInterface;

class ProxyList
{
    use \AwardWallet\Engine\ProxyList;

    protected ContainerInterface $services;

    public function __construct(ContainerInterface $webParsingServiceLocator)
    {
        $this->services = $webParsingServiceLocator;
    }

    public function getProxyList(): array
    {
        return $this->getRecaptchaProxies();
    }
}
