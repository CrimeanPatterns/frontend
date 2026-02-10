<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

class DataProviderAbstract implements DataProviderInterface
{
    /**
     * @var array
     */
    protected $options = [
        Mailer::OPTION_TRANSACTIONAL => false,
        Mailer::OPTION_EXTERNAL_CLICK_TRACKING => false,
        Mailer::OPTION_EXTERNAL_OPEN_TRACKING => true,
    ];

    public function next()
    {
        return true;
    }

    public function canSendEmail()
    {
        return true;
    }

    public function getMessage(Mailer $mailer)
    {
        return null;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function preSend(Mailer $mailer, \Swift_Message $message, &$options, bool $dryRun = false)
    {
    }

    public function postSend(Mailer $mailer, \Swift_Message $message, $options, $sendResult, bool $dryRun = false)
    {
    }
}
