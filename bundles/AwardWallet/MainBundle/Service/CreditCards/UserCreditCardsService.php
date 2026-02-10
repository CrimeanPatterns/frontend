<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Citi\CardOfferActivated;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserCreditCardsService
{
    private const DETECT_EXPIRED = [
        'Offer Expired',
        'has expired',
        'link has expired',
    ];
    private const DETECT_SUCCESS = [
        // 'Congratulations',
        'Activated the Offer',
    ];
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CreditCardMatcher $cardMatcher;
    private SafeExecutorFactory $safeExecutorFactory;
    private Mailer $mailer;
    private \CurlDriver $curlDriver;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CreditCardMatcher $cardMatcher,
        SafeExecutorFactory $safeExecutorFactory,
        Mailer $mailer,
        \CurlDriver $curlDriver
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cardMatcher = $cardMatcher;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->mailer = $mailer;
        $this->curlDriver = $curlDriver;
    }

    public function processEmailCallback(Usr $user, $data): ?bool
    {
        $providerCode = $data->providerCode;
        $cardPromo = $data->cardPromo;

        $offerActivatedLink = $cardPromo->offerDetails->applicationURL ?? null;
        $subject = $data->metadata->subject ?? null;

        $cardId = $this->safeExecutorFactory
            ->make(fn () => $this->cardMatcher->identify(
                $cardPromo->cardName,
                $this->entityManager->getRepository(Provider::class)->findOneBy(['code' => $providerCode])->getId()
            ))
            ->runOrNull();

        if (!$cardId) {
            return null;
        }

        $userCreditCard = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT UserCreditCardID, UNIX_TIMESTAMP(EarliestSeenDate) AS _EarliestSeenDate FROM UserCreditCard WHERE UserID = :userId AND CreditCardID = :cardId LIMIT 1',
            ['userId' => $user->getId(), 'cardId' => $cardId],
            ['userId' => \PDO::PARAM_INT, 'cardId' => \PDO::PARAM_INT]
        );

        $cardSince = strtotime($cardPromo->cardMemberSince . '-12-31 00:00:00');

        if (!$userCreditCard) {
            $this->entityManager->getConnection()->insert('UserCreditCard', [
                'UserId' => $user->getId(),
                'CreditCardId' => $cardId,
                'IsClosed' => 0,
                'EarliestSeenDate' => date('Y-m-d H:i', $cardSince),
                'LastSeenDate' => date('Y-m-d H:i:s'),
                'DetectedViaEmail' => 1,
                'SourcePlace' => UserCreditCard::SOURCE_PLACE_EMAIL,
            ]);

            if (!empty($offerActivatedLink) && !empty($subject)) {
                if ($this->activateOfferLink($user, $offerActivatedLink)) {
                    $this->logger->info('Send CitiOfferActivated begin');
                    $this->sendOfferEmail($user, $subject);
                }
            } else {
                $this->logger->info('Send CitiOfferActivated email data', [
                    'userId' => $user->getId(),
                    'offerLink' => $offerActivatedLink,
                    'emailSubject' => $subject,
                ]);
            }
        } else {
            $data = [
                'LastSeenDate' => date('Y-m-d H:i'),
                'IsClosed' => 0,
                'DetectedViaEmail' => 1,
                'SourcePlace' => UserCreditCard::SOURCE_PLACE_EMAIL,
            ];
            $criteria = ['UserCreditCardID' => $userCreditCard['UserCreditCardID']];

            if ($cardSince < (int) $userCreditCard['_EarliestSeenDate']) {
                $data['EarliestSeenDate'] = date('Y-m-d H:i:s', $cardSince);
            }
            $this->entityManager->getConnection()->update('UserCreditCard', $data, $criteria);
        }

        return true;
    }

    private function activateOfferLink(Usr $user, string $offerActivatedLink): bool
    {
        $http = new \HttpBrowser('none', $this->curlDriver);
        $response = $http->GetURL($offerActivatedLink, [], 7);

        if (!$response || !isset($http->Response->code) || Response::HTTP_OK !== $http->Response->code) {
            $this->logger->info('Send CitiOfferActivated email error', [
                'userId' => $user->getId(),
                'httpCode' => $http->Response->code ?? null,
            ]);

            return false;
        }

        $isExpired = false;

        foreach (self::DETECT_EXPIRED as $phrase) {
            if (false !== stripos($http->Response->body, $phrase)) {
                $isExpired = true;

                break;
            }
        }

        $isSuccess = false;

        foreach (self::DETECT_SUCCESS as $phrase) {
            if (false !== stripos($http->Response->body, $phrase)) {
                $isSuccess = true;

                break;
            }
        }

        if ($isSuccess && !$isExpired) {
            $this->logger->info('Send CitiOfferActivated email success', [
                'userId' => $user->getId(),
                'offerLink' => $offerActivatedLink,
            ]);
        } elseif ($isExpired) {
            $this->logger->info('Send CitiOfferActivated email expired', [
                'userId' => $user->getId(),
                'offerLink' => $offerActivatedLink,
            ]);
        } else {
            $this->logger->info('Send CitiOfferActivated email unknown', [
                'userId' => $user->getId(),
                'offerLink' => $offerActivatedLink,
            ]);
        }

        if (!$isSuccess) {
            return false;
        }

        return true;
    }

    private function sendOfferEmail(Usr $user, string $subject): void
    {
        $template = new CardOfferActivated($user);
        $template->offerSubject = $subject;

        $message = $this->mailer->getMessageByTemplate($template);

        // # TODO:: test
        $message->setTo('error@awardwallet.com');
        // test

        $this->mailer->send($message);
    }
}
