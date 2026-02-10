<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class TitledText extends BaseBlock
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $text;

    public function __construct($title, $text)
    {
        $this->setType('titledText');
        $this->title = $title;
        $this->text = $text;
    }
}
