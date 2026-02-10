<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception;

interface MailerExceptionInterface extends \Throwable
{
    public function getTarget(): \Swift_Message;
}
