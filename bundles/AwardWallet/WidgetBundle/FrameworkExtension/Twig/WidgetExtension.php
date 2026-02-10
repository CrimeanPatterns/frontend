<?php

namespace AwardWallet\WidgetBundle\FrameworkExtension\Twig;

use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\WidgetInterface;
use AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException;
use AwardWallet\WidgetBundle\Widget\LinkWidget;
use AwardWallet\WidgetBundle\WidgetFactory\Factory;

class WidgetExtension extends \Twig_Extension
{
    private $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('widget_render', [$this, 'render'], ['is_safe' => ['html']]),
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('LinkWidget', function (WidgetInterface $widget) { return $widget instanceof LinkWidget; }),
        ];
    }

    /**
     * Renders a widget.
     *
     * @param WidgetInterface|string $widget
     * @return string
     * @throws AuthenticationRequiredException
     * @throws \Exception
     */
    public function render($widget, array $options = [])
    {
        if (!($widget instanceof WidgetInterface)) {
            $widget = $this->factory->get($widget);
        }

        try {
            return $widget->render($options);
        } catch (AuthenticationRequiredException $e) {
            if ($widget instanceof UserWidgetInterface) {
                // dont show user specific widgets to anon
                return '';
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'widget';
    }
}
