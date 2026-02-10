<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

class ReauthResponse
{
    public const ACTION_ASK = 'ask';
    public const ACTION_AUTHORIZED = 'authorized';

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $dialogTitle;

    /**
     * @var string
     */
    public $inputTitle;

    /**
     * @var string
     */
    public $inputType;

    /**
     * @var string
     */
    public $context;

    /**
     * @var bool
     */
    public $resendAllowed;

    public static function authorized(): self
    {
        $result = new static();
        $result->action = self::ACTION_AUTHORIZED;

        return $result;
    }

    public static function ask(string $dialogTitle, string $inputTitle, string $inputType, string $context): self
    {
        $result = new static();
        $result->action = self::ACTION_ASK;
        $result->dialogTitle = $dialogTitle;
        $result->inputTitle = $inputTitle;
        $result->inputType = $inputType;
        $result->context = $context;

        return $result;
    }

    public function withResendFeature(): self
    {
        $this->resendAllowed = true;

        return $this;
    }
}
