<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailboxStatusHelper
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function mailboxIcon(Mailbox $mailbox): string
    {
        $state = $mailbox->getState();

        if ($state === "connecting") {
            return "loader";
        } elseif ($state === "scanning" || $state === "listening") {
            return "icon-green-check";
        }

        return "icon-red-error";
    }

    public function mailboxStatus(Mailbox $mailbox): string
    {
        $key = "mailbox.status." . $mailbox->getState();

        if (
            $mailbox instanceof OAuthMailbox
            && $mailbox->getState() === Mailbox::STATE_ERROR
            && $mailbox->getErrorCode() === Mailbox::ERROR_CODE_AUTHENTICATION) {
            $key .= ".connection-lost";
        } elseif ($mailbox->getState() === Mailbox::STATE_ERROR) {
            $key .= "." . $mailbox->getErrorCode();
        }

        return $this->translator->trans($key);
    }
}
