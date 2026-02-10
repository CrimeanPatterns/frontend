<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Action extends BaseBlock
{
    use ActionTrait;
    /**
     * @var string
     */
    public $name;

    /**
     * Action constructor.
     *
     * @param string $name
     * @param string $notice
     * @param string $link
     * @param string $method
     */
    public function __construct($name, $notice, $link, $method = "GET")
    {
        $this->setType('action');

        $this->name = $name;
        $this->link = $link;
        $this->notice = $notice;
        $this->method = $method;
    }
}
