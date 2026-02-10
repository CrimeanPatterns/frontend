<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Scanner\MailboxProgress;
use AwardWallet\MainBundle\Scanner\Messenger;
use AwardWallet\MainBundle\Scanner\ValidMailboxesUpdater;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\MailboxProgressEvent;
use AwardWallet\MainBundle\Service\EmailParsing\Client\ObjectSerializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MailboxProgressController
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $emailCallbackPassword;
    /**
     * @var MailboxProgress
     */
    private $mailboxProgress;
    /**
     * @var Messenger
     */
    private $messenger;
    /**
     * @var ValidMailboxesUpdater
     */
    private $validMailboxesUpdater;

    public function __construct(LoggerInterface $logger, string $emailCallbackPassword, MailboxProgress $mailboxProgress, Messenger $messenger, ValidMailboxesUpdater $validMailboxesUpdater)
    {
        $this->logger = $logger;
        $this->emailCallbackPassword = $emailCallbackPassword;
        $this->mailboxProgress = $mailboxProgress;
        $this->messenger = $messenger;
        $this->validMailboxesUpdater = $validMailboxesUpdater;
    }

    /**
     * @Route("/api/awardwallet/mailbox-progress", name="aw_mailbox_progress", methods={"POST"})
     */
    public function progressAction(Request $request)
    {
        $this->logger->info('request: ' . $request->getContent());

        if ($request->getUser() !== 'awardwallet'
            || $request->getPassword() !== $this->emailCallbackPassword
        ) {
            $this->logger->warning('unauthorized progress callback');

            return new Response('unauthorized', 401);
        }

        $events = json_decode($request->getContent());

        if (!is_array($events)) {
            return new Response('invalid data', 400);
        }

        $progress = [];

        foreach ($events as $eventData) {
            /** @var MailboxProgressEvent $event */
            $event = ObjectSerializer::deserialize($eventData, MailboxProgressEvent::class);

            if ($event === null) {
                return new Response('invalid data', 400);
            }

            $mailbox = $event->getMailbox();
            $userId = null;

            foreach ($mailbox->getTags() ?? [] as $tag) {
                if (preg_match('#^user_(\d+)$#ims', $tag, $matches)) {
                    $userId = (int) $matches[1];

                    break;
                }
            }

            if ($userId === null) {
                return new Response('invalid tags', 400);
            }

            if (!isset($progress[$userId])) {
                $progress[$userId] = [];
            }

            $progress[$userId][] = $mailbox;
        }

        foreach ($progress as $userId => $mailboxes) {
            $this->mailboxProgress->sendProgressUpdates($userId, $mailboxes);
            $this->messenger->mailboxesStateChanged($mailboxes);
            $this->validMailboxesUpdater->updateCounter($userId);
        }

        return new Response("ok");
    }
}
