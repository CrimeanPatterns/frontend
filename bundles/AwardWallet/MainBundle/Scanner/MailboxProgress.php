<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use Psr\Log\LoggerInterface;

class MailboxProgress
{
    /**
     * @var MailboxStatusHelper
     */
    private $mailboxStatusHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ClientInterface
     */
    private $centrifuge;
    /**
     * @var Mobile\Actions
     */
    private $mobileActions;

    public function __construct(
        MailboxStatusHelper $mailboxStatusHelper,
        LoggerInterface $logger,
        ClientInterface $centrifuge,
        Mobile\Actions $mobileActions
    ) {
        $this->mailboxStatusHelper = $mailboxStatusHelper;
        $this->logger = $logger;
        $this->centrifuge = $centrifuge;
        $this->mobileActions = $mobileActions;
    }

    /**
     * @param Mailbox[] $mailboxes
     */
    public function sendProgressUpdates(int $userId, array $mailboxes)
    {
        $channel = '$mailboxes_' . $userId;

        foreach ($mailboxes as $mailbox) {
            $update = [
                "id" => $mailbox->getId(),
                "status" => $this->mailboxStatusHelper->mailboxStatus($mailbox),
                "icon" => $this->mailboxStatusHelper->mailboxIcon($mailbox),
                "actions" => $this->mobileActions->getActions($mailbox),
            ];
            $this->logger->info("sending mailbox update",
                array_merge($update, ["mailboxId" => $mailbox->getId(), "userId" => $userId]));
            $this->centrifuge->publish($channel, $update);
        }
    }
}
