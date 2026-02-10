<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractWidget implements WidgetInterface
{
    use ContainerAwareTrait { setContainer as traitSetContainer; }

    /**
     * @var bool
     */
    protected $initialized;

    /**
     * @var bool
     */
    protected $visible;

    public function __construct()
    {
        $this->visible = true;
        $this->initialized = false;
    }

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->traitSetContainer($container);

        try {
            $this->init();
        } catch (AuthenticationRequiredException $e) {
            if ($this instanceof UserWidgetInterface) {
                $this->hide();
            } else {
                throw $e;
            }
        }
    }

    public function init()
    {
        if ($this->initialized == true) {
            return true;
        }
        $this->initialized = true;

        return false;
    }

    public function show()
    {
        $this->visible = true;
    }

    public function hide()
    {
        $this->visible = false;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    final public function render($options = [])
    {
        if ($this->isVisible()) {
            return $this->getWidgetContent($options);
        }

        return '';
    }
}
