<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

class SendReport
{
    /**
     * @var bool
     */
    public $success;

    /**
     * @var string|null
     */
    public $recepient;

    public function __construct(bool $success, ?string $recepient = null)
    {
        $this->success = $success;
        $this->recepient = $recepient;
    }
}
