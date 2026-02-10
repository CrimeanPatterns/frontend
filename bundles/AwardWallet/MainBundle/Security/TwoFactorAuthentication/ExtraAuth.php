<?php

namespace AwardWallet\MainBundle\Security\TwoFactorAuthentication;

class ExtraAuth
{
    public $type;
    public $requiredMessage;
    public $invalidMessage;
    public $inputLabel;
    private $isValidCodeCallback;
    private $markCodeAsUsedCallback;

    public function __construct($type, $requiredMessage, $invalidMessage, $inputLabel, $isValidCode, $markCodeAsUsed)
    {
        $this->type = $type;
        $this->requiredMessage = $requiredMessage;
        $this->invalidMessage = $invalidMessage;
        $this->inputLabel = $inputLabel;
        $this->isValidCodeCallback = $isValidCode;
        $this->markCodeAsUsedCallback = $markCodeAsUsed;
    }

    public function isValidCode($code)
    {
        return call_user_func($this->isValidCodeCallback, $code);
    }

    public function markCodeAsUsed($code)
    {
        return call_user_func($this->markCodeAsUsedCallback, $code);
    }
}
