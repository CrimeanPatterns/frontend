<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;

class WriteCheck extends Message
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $replacements;

    public function setMessage(string $message): WriteCheck
    {
        $this->message = $message;

        return $this;
    }

    public function setReplacements(array $replacements): WriteCheck
    {
        $this->replacements = $replacements;

        return $this;
    }
}
