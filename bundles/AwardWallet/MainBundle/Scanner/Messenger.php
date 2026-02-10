<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\MailboxConnectionLost;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateOAuthMailboxRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class Messenger
{
    private const LAST_USER_MESSAGE_KEY = 'scanner_last_user_message_';
    private const MIN_PAUSE_BETWEEN_USER_MESSAGES = 86400 * 7; // https://redmine.awardwallet.com/issues/23422#note-7

    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var WarningGenerator
     */
    private $warningGenerator;
    /**
     * @var Mailer
     */
    private $mailer;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var UsrRepository
     */
    private $usrRepository;
    /**
     * @var EmailScannerApi
     */
    private $emailScannerApi;

    public function __construct(
        \Memcached $memcached,
        LoggerInterface $logger,
        WarningGenerator $warningGenerator,
        Mailer $mailer,
        RouterInterface $router,
        UsrRepository $usrRepository,
        EmailScannerApi $emailScannerApi
    ) {
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->warningGenerator = $warningGenerator;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->usrRepository = $usrRepository;
        $this->emailScannerApi = $emailScannerApi;
    }

    /**
     * @param Mailbox[] $mailboxes
     */
    public function mailboxesStateChanged(array $mailboxes)
    {
        $mailbox = $this->warningGenerator->selectUnauthorizedMailbox($mailboxes);

        if ($mailbox === null) {
            return;
        }

        $this->notifyUser($mailbox);
    }

    public static function resetNotificationsData(array $userData)
    {
        unset($userData['lastNotification']);

        return $userData;
    }

    public function wantToNotifyUser(Mailbox $mailbox): bool
    {
        $userData = json_decode($mailbox->getUserData(), true);

        if (!is_array($userData) || !isset($userData['user'])) {
            $this->logger->warning("invalid user data for mailbox {$mailbox->getId()}");

            return false;
        }

        if ($mailbox->getState() === Mailbox::STATE_LISTENING && isset($userData['firstNotification'])) {
            $this->logger->info("mailbox returned to listening state, resetting firstNotification", ["MailboxID" => $mailbox->getId()]);
            unset($userData['firstNotification']);
            unset($userData['firstNotificationKnown']);
            $this->emailScannerApi->putMailbox($mailbox->getId(), new UpdateOAuthMailboxRequest([
                'userData' => json_encode($userData),
            ]));

            return false;
        }

        if (WarningGenerator::isAuthorizationNeeded($mailbox)) {
            $userId = $userData['user'];

            if ($this->memcached->get(self::LAST_USER_MESSAGE_KEY . $userId) !== false) {
                $this->logger->debug("we have recently sent message about mailboxes to this user, skip now, userId: {$userId}");

                return false;
            }

            $lastNotification = $userData["lastNotification"] ?? 0;
            $firstNotification = $userData["firstNotification"] ?? 0;

            if ((time() - $firstNotification) > (30 * 86400)) {
                $this->logger->info("mailbox is in error state more than 30 days, will notify with 30 days period", ["MailboxID" => $mailbox->getId()]);
                $period = 30 * 86400;
            } else {
                $period = 7 * 86400;
            }

            if ((time() - $lastNotification) < $period) {
                $this->logger->debug("we have recently sent message about this mailbox, skip now, mailboxId {$mailbox->getId()}");

                return false;
            }

            return true;
        }

        return false;
    }

    public function notifyUser(Mailbox $mailbox): bool
    {
        if ($this->wantToNotifyUser($mailbox)) {
            $userData = json_decode($mailbox->getUserData(), true);
            $userId = $userData['user'];

            $this->logger->info("notifying user about mailbox", ["MailboxID" => $mailbox->getId()]);

            if (!isset($userData['firstNotification']) && !isset($userData['lastNotification'])) {
                $this->logger->info("setting firstNotification", ["MailboxID" => $mailbox->getId()]);
                $userData['firstNotification'] = time();
            }

            $userData['lastNotification'] = time();
            $this->emailScannerApi->putMailbox($mailbox->getId(), new UpdateOAuthMailboxRequest([
                'userData' => json_encode($userData),
            ]));

            $this->memcached->set(self::LAST_USER_MESSAGE_KEY . $userId, time(), self::MIN_PAUSE_BETWEEN_USER_MESSAGES);

            $this->sendMessage($userId, $mailbox);

            return true;
        }

        return false;
    }

    private function sendMessage(int $userId, OAuthMailbox $mailbox)
    {
        $template = new MailboxConnectionLost();
        $template->mailboxId = $mailbox->getId();
        $template->mailboxEmail = $mailbox->getEmail();
        $template->link = $this->router->generate('aw_usermailbox_update_oauth', ['mailboxId' => $mailbox->getId()],
            RouterInterface::ABSOLUTE_URL);
        $user = $this->usrRepository->find($userId);

        if ($user === null) {
            $this->logger->warning("user $userId not found, will not send mailbox warning");

            return;
        }
        $template->toUser($user);
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }
}
