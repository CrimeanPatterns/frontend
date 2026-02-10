<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

/**
 * @deprecated use iterators, eg AwardWallet\MainBundle\Globals\Utils\IteratorFluent
 * example of use \AwardWallet\MainBundle\Command\NotifyExpiredCommand
 */
interface DataProviderInterface
{
    /**
     * @return bool
     */
    public function next();

    /**
     * @return bool
     */
    public function canSendEmail();

    /**
     * @return \Swift_Message
     * @throws SkipException
     */
    public function getMessage(Mailer $mailer);

    /**
     * @return array
     */
    public function getOptions();

    public function preSend(Mailer $mailer, \Swift_Message $message, &$options, bool $dryRun = false);

    /**
     * @param bool $sendResult
     */
    public function postSend(Mailer $mailer, \Swift_Message $message, $options, $sendResult, bool $dryRun = false);
}
