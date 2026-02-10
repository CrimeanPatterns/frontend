<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

class Message extends \Swift_Message implements MessageContextInterface
{
    private $context = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function addContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function setTo($addresses, $name = null)
    {
        try {
            return parent::setTo($addresses, $name);
        } catch (\Swift_RfcComplianceException $e) {
            return parent::setTo([]);
        }
    }

    public function setCc($addresses, $name = null)
    {
        try {
            return parent::setCc($addresses, $name);
        } catch (\Swift_RfcComplianceException $e) {
            return parent::setCc([]);
        }
    }

    public function setBcc($addresses, $name = null)
    {
        try {
            return parent::setBcc($addresses, $name);
        } catch (\Swift_RfcComplianceException $e) {
            return parent::setBcc([]);
        }
    }
}
