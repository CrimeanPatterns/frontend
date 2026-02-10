<?php

namespace AwardWallet\MainBundle\Scanner\Mobile;

use AwardWallet\MainBundle\Scanner\MailboxOwnerHelper;
use AwardWallet\MainBundle\Scanner\MailboxStatusHelper;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ListItemFormatter
{
    /**
     * @var MailboxOwnerHelper
     */
    private $mailboxOwnerHelper;
    /**
     * @var MailboxStatusHelper
     */
    private $mailboxStatusHelper;

    public function __construct(
        MailboxOwnerHelper $mailboxOwnerHelper,
        MailboxStatusHelper $mailboxStatusHelper
    ) {
        $this->mailboxOwnerHelper = $mailboxOwnerHelper;
        $this->mailboxStatusHelper = $mailboxStatusHelper;
    }

    public function format(Mailbox $mailbox): array
    {
        return [
            'id' => $mailbox['id'],
            'state' => $mailbox['state'],
            'email' => (Mailbox::TYPE_IMAP === $mailbox['type']) ? $mailbox['login'] : $mailbox['email'],
            'icon' => $this->mailboxStatusHelper->mailboxIcon($mailbox),
            'status' => $this->mailboxStatusHelper->mailboxStatus($mailbox),
            'owner' => $this->mailboxOwnerHelper->getOwnerByUserData($mailbox->getUserData())->getFullName(),
            'actions' => it(call(function () use ($mailbox) {
                if ($mailbox->getErrorCode() === Mailbox::ERROR_CODE_AUTHENTICATION) {
                    yield Actions::UPDATE;
                }

                yield Actions::DELETE;
            }))->toArray(),
        ];
    }
}
