<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;

class CollectionWidget extends AbstractWidgetContainer
{
    public function __construct($collection = [])
    {
        parent::__construct();

        foreach ($collection as $key => $item) {
            $this->addItem($item, $key);
        }
    }

    public function hide()
    {
        parent::hide();

        foreach ($this->widgets as $widget) {
            $widget->hide();
        }
    }

    public function show()
    {
        parent::show();

        foreach ($this->widgets as $widget) {
            $widget->show();
        }
    }

    public function getWidgetContent($options = [])
    {
        return '';
    }
}
