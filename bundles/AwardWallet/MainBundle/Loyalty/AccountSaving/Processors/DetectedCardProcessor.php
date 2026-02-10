<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\DetectedCard;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\DetectedCard as LoyaltyDetectedCard;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DetectedCardProcessor
{
    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private CreditCardMatcher $ccMatcher;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, CreditCardMatcher $ccMatcher)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->ccMatcher = $ccMatcher;
    }

    public function process(Account $account, CheckAccountResponse $response)
    {
        $detectedCards = $response->getDetectedcards();
        $providerId = $account->getProviderid()->getId();

        if (!is_array($detectedCards) || count($detectedCards) === 0) {
            $affected = $this->em
                ->getConnection()
                ->executeStatement("DELETE FROM DetectedCard WHERE AccountID = ?", [$account->getId()]);
            $this->logger->info(sprintf('account #%d, deleted detected cards: %d', $account->getId(), $affected));

            return;
        }

        $affected = $this->em
            ->getConnection()
            ->executeStatement(
                "DELETE FROM DetectedCard WHERE AccountID = ? AND Code NOT IN (?)",
                [
                    $account->getId(),
                    $codes = array_map(fn (LoyaltyDetectedCard $detectedCard) => $detectedCard->getCode(), $detectedCards),
                ],
                [
                    \PDO::PARAM_INT,
                    Connection::PARAM_STR_ARRAY,
                ]
            );

        $this->logger->info(sprintf('account #%d, saving detected cards: %s, deleted: %d', $account->getId(),
            json_encode($codes), $affected));

        /** @var LoyaltyDetectedCard $detectedCard */
        foreach ($detectedCards as $loyaltyDetectedCard) {
            $code = $loyaltyDetectedCard->getCode();

            // TODO tmp
            if (empty($code) || empty($loyaltyDetectedCard->getDisplayname()) || empty($loyaltyDetectedCard->getCarddescription())) {
                $this->logger->error("detected card with empty field(s)",
                    [
                        'accountId' => $account->getId(),
                        'code' => $code,
                        'displayName' => $loyaltyDetectedCard->getDisplayname(),
                        'discription' => $loyaltyDetectedCard->getCarddescription(),
                        'accountUpdateDate' => $account->getUpdatedate()->format('Y-m-d H:i'),
                    ]);

                continue;
            }

            $cardsInDb = $this->em->getRepository(DetectedCard::class)
                ->findBy(['code' => $code, 'account' => $account->getId()]);
            $detectedCard = null;

            foreach ($cardsInDb as $cardInDb) {
                if (null === $detectedCard) {
                    $detectedCard = $cardInDb;
                } else {
                    $this->em->remove($cardInDb);
                }
            }

            if (empty($detectedCard)) {
                $detectedCard = (new DetectedCard())
                    ->setCode($code)
                    ->setAccount($account);
            }

            $this->saveDetectedCard($detectedCard, $loyaltyDetectedCard->getDisplayname(),
                $loyaltyDetectedCard->getCarddescription(), $providerId, $account->getId());

            $this->em->persist($detectedCard);
            $this->em->flush();
        }
    }

    private function saveDetectedCard(DetectedCard $detectedCard, $displayName, $description, $providerId, $accountId)
    {
        $detectedCard
            ->setDescription($description)
            ->setDisplayname($displayName);

        if (null !== ($creditCardId = $this->ccMatcher->identify($displayName, $providerId))) {
            $creditCard = $this->em->getRepository(CreditCard::class)->find($creditCardId);
            $detectedCard->setCreditCard($creditCard);
        }

        if (null !== ($subAccount = $this->em->getRepository(Subaccount::class)
                ->findOneBy(['code' => $detectedCard->getCode(), 'accountid' => $accountId]))) {
            $detectedCard->setSubAccount($subAccount);
        }
    }
}
