<?php

namespace AwardWallet\MobileBundle\Form\View;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class UrlTransformer implements UrlGeneratorInterface
{
    /**
     * @var string
     */
    private $prefix;
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router, $prefix)
    {
        $this->router = $router;
        $this->prefix = $prefix;
    }

    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    public function getContext()
    {
        $this->router->getContext();
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $url = $this->router->generate($name, $parameters, $referenceType);

        if (strpos(strtolower($url), $this->prefix) === 0) {
            $url = substr($url, strlen($this->prefix));
        }

        return $url;
    }
}
