<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use Psr\Log\LoggerInterface;

class WarningGenerator
{
    public const DEFAULT_ALLOWED_TYPES = [Mailbox::TYPE_GOOGLE, Mailbox::TYPE_MICROSOFT, Mailbox::TYPE_YAHOO, Mailbox::TYPE_AOL];

    private const API_FAIL_KEY = 'scanner_api_fail';
    private const USER_REMINDER_KEY = 'scanner_mailbox_failed_user_2_';
    private const REMINDER_FREQUENCY_SECONDS = 3600 * 12;

    /**
     * @var EmailScannerApi
     */
    private $emailScannerApi;
    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EmailScannerApi $emailScannerApi, AwTokenStorage $tokenStorage, \Memcached $memcached, LoggerInterface $logger)
    {
        $this->emailScannerApi = $emailScannerApi;
        $this->tokenStorage = $tokenStorage;
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    public function getUnauthorizedMailbox(array $allowedTypes = self::DEFAULT_ALLOWED_TYPES): ?Mailbox
    {
        if ($this->memcached->get(self::API_FAIL_KEY)) {
            $this->logger->warning("mailbox scanner api marked as failed, will not contact it");

            return null;
        }

        $userId = $this->tokenStorage->getBusinessUser()->getUserid();

        if ($this->memcached->get(self::USER_REMINDER_KEY . $userId) !== false) {
            return null;
        }

        try {
            $mailboxes = $this->emailScannerApi->listMailboxes(["user_" . $userId]);
        } catch (\Exception $exception) {
            $this->logger->critical("could not access scanner api: " . $exception->getMessage());
            $this->memcached->set(self::API_FAIL_KEY, time(), 300);

            return null;
        }

        $result = $this->selectUnauthorizedMailbox($mailboxes, $allowedTypes);

        $this->memcached->set(self::USER_REMINDER_KEY . $userId, time(), self::REMINDER_FREQUENCY_SECONDS);

        return $result;
    }

    public function selectUnauthorizedMailbox(array $mailboxes, array $allowedTypes = self::DEFAULT_ALLOWED_TYPES): ?Mailbox
    {
        foreach ($mailboxes as $mailbox) {
            if (self::isAuthorizationNeeded($mailbox, $allowedTypes)) {
                return $mailbox;
            }
        }

        return null;
    }

    public static function isAuthorizationNeeded(Mailbox $mailbox, array $allowedTypes = self::DEFAULT_ALLOWED_TYPES): bool
    {
        return
            \in_array($mailbox->getType(), $allowedTypes, true)
            && ($mailbox->getState() === Mailbox::STATE_ERROR)
            && ($mailbox->getErrorCode() === Mailbox::ERROR_CODE_AUTHENTICATION);
    }
}
