<?php

namespace AwardWallet\WidgetBundle\WidgetFactory;

use AwardWallet\WidgetBundle\Widget\Classes\WidgetInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Factory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        ContainerInterface $container,
        Router $router,
        TokenStorageInterface $tokenStorage,
        EntityManager $em
    ) {
        $this->container = $container;
        $this->router = $router;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function get($widget)
    {
        if ($this->container->has('aw.widget.' . $widget)) {
            $widget = $this->container->get('aw.widget.' . $widget);
        } elseif ($this->container->has($widget)) {
            $widget = $this->container->get($widget);
        }

        if (!($widget instanceof WidgetInterface)) {
            throw new \Exception('Cannot get widget');
        }

        return $widget;
    }
}
