<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;

class UpdateRequest extends Message
{
    /**
     * @var string
     */
    public $message;

    public function setMessage(string $message): UpdateRequest
    {
        $this->message = $message;

        return $this;
    }
}
