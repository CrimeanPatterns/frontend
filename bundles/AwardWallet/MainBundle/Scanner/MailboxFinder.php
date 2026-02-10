<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ImapMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;

use function AwardWallet\MainBundle\Globals\Utils\f\andX;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MailboxFinder
{
    /**
     * @var EmailScannerApi
     */
    private $emailScannerApi;

    public function __construct(EmailScannerApi $emailScannerApi)
    {
        $this->emailScannerApi = $emailScannerApi;
    }

    public function findById(Usr $user, int $id): ?Mailbox
    {
        return
            it($this->findAllByUser($user))
            ->find(function (Mailbox $mailbox) use ($id) { return (int) $mailbox->getId() === $id; });
    }

    public function findFirstByEmailAndType(Usr $user, string $email, string $type): ?Mailbox
    {
        return
            it($this->findAllByUser($user))
            ->find(
                andX(
                    $this->getEmailFilter($email),
                    $this->getTypeFilter($type)
                )
            );
    }

    public function findAllByUser(Usr $user): array
    {
        return $this->emailScannerApi->listMailboxes(["user_" . $user->getUserid()]);
    }

    /**
     * @return string[]
     */
    public function findAllEmailAddressesByUser(Usr $user): array
    {
        return it($this->findAllByUser($user))
            ->map(function (Mailbox $mailbox) {
                if ($mailbox instanceof ImapMailbox) {
                    return $mailbox->getLogin();
                } elseif ($mailbox instanceof OAuthMailbox) {
                    return $mailbox->getEmail();
                }

                return null;
            })
            ->filterNotNull()
            ->toArray();
    }

    protected function getTypeFilter(string $type): callable
    {
        return function (Mailbox $mailbox) use ($type) {
            return $mailbox->getType() === $type;
        };
    }

    protected function getEmailFilter(string $email): callable
    {
        return function (Mailbox $mailbox) use ($email) {
            if ($mailbox instanceof ImapMailbox) {
                $mailboxEmail = $mailbox->getLogin();
            } elseif ($mailbox instanceof OAuthMailbox) {
                $mailboxEmail = $mailbox->getEmail();
            } else {
                return false;
            }

            return \strcasecmp($mailboxEmail, $email) == 0;
        };
    }
}
