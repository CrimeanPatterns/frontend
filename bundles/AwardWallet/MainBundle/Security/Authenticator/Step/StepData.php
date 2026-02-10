<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\Question;

class StepData
{
    /**
     * @var ?string
     */
    protected $csrfToken;

    /**
     * @var ?string
     */
    protected $login;

    /**
     * @var ?string
     */
    protected $password;

    /**
     * @var ?string
     */
    protected $otcAppCode;

    /**
     * @var ?string
     */
    protected $otcEmailCode;

    /**
     * @var ?string
     */
    protected $otcRecoveryCode;

    /**
     * @var Question[]
     */
    protected $questions = [];

    /**
     * @var ?string
     */
    protected $scripted;

    /**
     * @var string
     */
    protected $recaptcha;

    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return StepData
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return StepData
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getOtcAppCode()
    {
        return $this->otcAppCode;
    }

    /**
     * @return StepData
     */
    public function setOtcAppCode($otcAppCode)
    {
        $this->otcAppCode = $otcAppCode;

        return $this;
    }

    public function getOtcEmailCode()
    {
        return $this->otcEmailCode;
    }

    /**
     * @return StepData
     */
    public function setOtcEmailCode($otcEmailCode)
    {
        $this->otcEmailCode = $otcEmailCode;

        return $this;
    }

    public function getOtcRecoveryCode()
    {
        return $this->otcRecoveryCode;
    }

    /**
     * @return StepData
     */
    public function setOtcRecoveryCode($otcRecoveryCode)
    {
        $this->otcRecoveryCode = $otcRecoveryCode;

        return $this;
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @param Question[] $questions
     */
    public function setQuestions(array $questions): StepData
    {
        $this->questions = $questions;

        return $this;
    }

    public function getCsrfToken()
    {
        return $this->csrfToken;
    }

    /**
     * @return StepData
     */
    public function setCsrfToken($csrfToken)
    {
        $this->csrfToken = $csrfToken;

        return $this;
    }

    public function getScripted()
    {
        return $this->scripted;
    }

    /**
     * @return StepData
     */
    public function setScripted($scripted)
    {
        $this->scripted = $scripted;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecaptcha()
    {
        return $this->recaptcha;
    }

    public function setRecaptcha(?string $recaptcha = null): StepData
    {
        $this->recaptcha = $recaptcha;

        return $this;
    }
}
