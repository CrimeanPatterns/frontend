<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;

class ChangeStatusRequest extends Message
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $replacements;

    /**
     * @var string
     */
    public $statusCode;

    /**
     * @var string
     */
    public $statusDesc;

    public function setMessage(string $message): ChangeStatusRequest
    {
        $this->message = $message;

        return $this;
    }

    public function setReplacements(array $replacements): ChangeStatusRequest
    {
        $this->replacements = $replacements;

        return $this;
    }

    public function setStatusCode(string $statusCode): ChangeStatusRequest
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function setStatusDesc(string $statusDesc): ChangeStatusRequest
    {
        $this->statusDesc = $statusDesc;

        return $this;
    }
}
