<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidget;

class TextWidget extends AbstractWidget
{
    protected $text;

    public function __construct($text)
    {
        parent::__construct();

        $this->text = $text;
    }

    public function getWidgetContent($options = [])
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }
}
