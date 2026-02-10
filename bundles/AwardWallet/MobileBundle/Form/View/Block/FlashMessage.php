<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class FlashMessage extends BaseBlock
{
    use ActionTrait;
    /**
     * @var string
     */
    public $message;
    /**
     * @var string
     */
    public $status;

    /**
     * FlashMessage constructor.
     *
     * @param string $message
     * @param string $status
     */
    public function __construct($message, $status)
    {
        $this->setType('flashMessage');

        $this->message = $message;
        $this->status = $status;
    }
}
