<?php

namespace AwardWallet\MainBundle\Form\Account;

/**
 * @deprecated
 */
class Message
{
    /**
     * @var string
     */
    public $text;
    /**
     * @var string
     */
    public $class;
    /**
     * @var string
     */
    public $icon;
    /**
     * @var string
     */
    public $title;

    public function __construct($text, $class = null, $icon = null, $title = null)
    {
        $this->text = $text;
        $this->class = $class;
        $this->icon = $icon;
        $this->title = $title;
    }
}
