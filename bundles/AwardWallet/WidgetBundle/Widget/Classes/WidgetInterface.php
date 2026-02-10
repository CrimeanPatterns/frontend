<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface WidgetInterface extends ContainerAwareInterface
{
    /**
     * @return void
     */
    public function init();

    /**
     * @return void
     */
    public function show();

    /**
     * @return void
     */
    public function hide();

    /**
     * Recursive test current widget and sub-widgets on visibility.
     *
     * @return bool
     */
    public function isVisible();

    /**
     * @param array $options
     * @return string
     */
    public function render($options = []);

    /**
     * @param array $options
     * @return string
     */
    public function getWidgetContent($options = []);
}
