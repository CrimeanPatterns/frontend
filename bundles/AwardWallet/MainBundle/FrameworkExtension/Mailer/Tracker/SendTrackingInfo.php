<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

class SendTrackingInfo
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var \Swift_Message
     */
    private $message;
    /**
     * @var string
     */
    private $bodyBackup;

    public function __construct(string $id, \Swift_Message $message, ?string $bodyBackup)
    {
        $this->id = $id;
        $this->message = $message;
        $this->bodyBackup = $bodyBackup;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessage(): \Swift_Message
    {
        return $this->message;
    }

    public function getBodyBackup(): ?string
    {
        return $this->bodyBackup;
    }
}
