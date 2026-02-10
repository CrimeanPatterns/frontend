<?php

namespace AwardWallet\MainBundle\Security\TwoFactorAuthentication;

use AwardWallet\MainBundle\Security\TranslatedAuthenticationException;

class TwoFactorAuthenticationException extends TranslatedAuthenticationException
{
    /**
     * label for one-time-code input, for example: 'one-time code' or 'one-time code from email'.
     *
     * @var string
     */
    private $inputLabel;
    /**
     * @var string
     */
    private $inputHint;

    /**
     * show recovery options.
     *
     * @var bool
     */
    private $showRecovery;
    /**
     * @var array
     */
    private $questions;

    public function __construct($message, $inputLabel = null, $inputHint = null, $showRecovery = true, $questions = [])
    {
        parent::__construct($message);
        $this->inputLabel = $inputLabel;
        $this->inputHint = $inputHint;
        $this->showRecovery = $showRecovery;
        $this->questions = $questions;
    }

    public function getInputLabel()
    {
        return $this->inputLabel;
    }

    public function getInputHint()
    {
        return $this->inputHint;
    }

    public function getShowRecovery()
    {
        return $this->showRecovery;
    }

    public function getQuestions()
    {
        return $this->questions;
    }
}
