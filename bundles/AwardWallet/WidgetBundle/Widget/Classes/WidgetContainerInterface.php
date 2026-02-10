<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

interface WidgetContainerInterface
{
    /**
     * @return void
     */
    public function addItem(WidgetInterface $widget);
}
