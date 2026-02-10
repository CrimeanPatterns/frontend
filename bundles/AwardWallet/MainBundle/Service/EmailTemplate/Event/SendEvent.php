<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Event;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Symfony\Component\EventDispatcher\Event;

class SendEvent extends Event
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var \Swift_Message
     */
    private $message;

    /** @var bool */
    private $success = false;
    private bool $dryRun = false;

    public function __construct(Mailer $mailer, \Swift_Message $message, $success = false, bool $dryRun = false)
    {
        $this->mailer = $mailer;
        $this->message = $message;
        $this->success = $success;
        $this->dryRun = $dryRun;
    }

    /**
     * @return Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @return \Swift_Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}
