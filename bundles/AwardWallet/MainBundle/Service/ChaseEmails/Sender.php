<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Psr\Log\LoggerInterface;

class Sender
{
    private UsrRepository $usrRepository;

    private Mailer $mailer;

    private EmailLog $emailLog;

    private LoggerInterface $logger;

    public function __construct(UsrRepository $usrRepository, Mailer $mailer, EmailLog $emailLog, LoggerInterface $logger)
    {
        $this->usrRepository = $usrRepository;
        $this->mailer = $mailer;
        $this->emailLog = $emailLog;
        $this->logger = $logger;
    }

    /**
     * @return bool - message was sent
     */
    public function sendEmail(int $userId, ?string $testEmail, string $templateName, array $templateParams, int $cardId): bool
    {
        /** @var Usr $user */
        $user = $this->usrRepository->find($userId);

        if ($user === null) {
            $this->logger->warning("user $userId not found");

            return false;
        }

        $templateClass = 'AwardWallet\\MainBundle\\FrameworkExtension\\Mailer\\Template\\Offer\\Chase\\' . $templateName;
        $templateObject = new $templateClass($user);

        foreach ($templateParams as $key => $value) {
            if (property_exists($templateObject, $key)) {
                $templateObject->$key = $value;
            }
        }

        $message = $this->mailer->getMessageByTemplate($templateObject);

        if ($testEmail !== null) {
            $testEmails = explode(",", $testEmail);
            $testEmails = array_combine($testEmails, array_fill(0, count($testEmails), $user->getFullName() . " (" . $user->getUserid() . ") at " . $user->getEmail()));
            $message->setTo($testEmails);
        } else {
            $message->setTo($user->getEmail(), $user->getFullName());
        }
        $this->mailer->send($message);

        if ($testEmail === null) {
            $this->emailLog->recordEmailToLog($userId, EmailLog::MESSAGE_KIND_CHASE, $templateName, $cardId);
        }

        return true;
    }
}
