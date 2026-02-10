<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Paragraph extends BaseBlock
{
    /**
     * @var string
     */
    public $text;

    public function __construct($text)
    {
        $this->setType('paragraph');
        $this->text = $text;
    }
}
