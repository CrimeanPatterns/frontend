<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

trait ActionTrait
{
    /**
     * @var string
     */
    public $link;
    /**
     * @var string
     */
    public $notice;
    /**
     * @var string
     */
    public $method;

    /**
     * @param string $link
     * @return ActionTrait
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @param string $notice
     * @return ActionTrait
     */
    public function setNotice($notice)
    {
        $this->notice = $notice;

        return $this;
    }

    /**
     * @param string $method
     * @return ActionTrait
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
