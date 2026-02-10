<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidget;

class MenuWidgetExtended extends AbstractWidget
{
    public $items = [];
    public $ulClass;

    public function __construct($arguments)
    {
        $this->ulClass = $arguments['ulclass'] ?? null;
        parent::__construct();
    }

    public function setUlClass($ulClass)
    {
        $this->ulClass = $ulClass;
    }

    /**
     * @param array $options
     * @return string
     */
    public function getWidgetContent($options = [])
    {
        $items = array_merge($this->items, $options);

        return $this->container->get('twig')->render('@AwardWalletWidget/menu_extended.html.twig', [
            'items' => $items,
            'ulclass' => $this->ulClass,
        ]);
    }

    /**
     * @param array $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }
}
