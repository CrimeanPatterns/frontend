<?php

namespace AwardWallet\MainBundle\Security;

class LoginResponse
{
    /**
     * @var bool
     */
    public $success = false;
    /**
     * @var string
     */
    public $message;
    /**
     * @var bool
     */
    public $otcRequired;
    /**
     * @var bool
     */
    public $otcShowRecovery;
    /**
     * @var string
     */
    public $otcInputLabel;
    /**
     * @var string
     */
    public $otcInputHint;
    /**
     * @var bool
     */
    public $isError;

    public function __construct($success, $message, $otcRequired = false, $otcInputLabel = null, $otcInputHint = null, $otcShowRecovery = true, $isError = true)
    {
        $this->success = $success;
        $this->message = $message;
        $this->otcRequired = $otcRequired;
        $this->otcInputLabel = $otcInputLabel;
        $this->otcShowRecovery = $otcShowRecovery;
        $this->otcInputHint = $otcInputHint;
        $this->isError = $isError;
    }
}
