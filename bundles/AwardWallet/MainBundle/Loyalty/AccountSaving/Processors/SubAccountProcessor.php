<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubAccountProcessor
{
    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private CreditCardMatcher $matcher;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, CreditCardMatcher $matcher)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->matcher = $matcher;
    }

    /* TODO: переделать интерфейс на process(Account, CheckAccountResponse) после релиза v2 itineraries */
    public function process(Account $account, CheckAccountResponse $response): void
    {
        /* TODO: сперва логика сохранения SubAccount затем следующие проверки для детекта CreditCard */
        $subAccounts = is_array($response->getSubaccounts()) ? $response->getSubaccounts() : [];

        if (count($subAccounts) > 0) {
            $affected = $this->em->getConnection()->executeStatement("
                    DELETE FROM SubAccount WHERE AccountID = ? AND Code NOT IN (?)
                ",
                [$account->getId(), $codes = array_map(fn (\AwardWallet\MainBundle\Loyalty\Resources\SubAccount $subAccount) => $subAccount->getCode(), $subAccounts)],
                [
                    \PDO::PARAM_INT,
                    Connection::PARAM_STR_ARRAY,
                ]
            );

            $this->logger->info(sprintf('account #%d, saving subaccounts: %s, deleted: %d', $account->getId(), json_encode($codes), $affected));
        } else {
            $affected = $this->em->getConnection()->executeStatement("DELETE FROM SubAccount WHERE AccountID = ?", [$account->getId()]);
            $this->logger->info(sprintf('account #%d, deleted subaccounts: %d', $account->getId(), $affected));
        }

        $account->setSubaccounts(count($subAccounts));
        $this->logger->info(sprintf('account #%d, set total balance: %.2f', $account->getId(), $account->getTotalbalance()));

        if (!in_array($account->getProviderid()->getId(), Provider::EARNING_POTENTIAL_LIST)) {
            return;
        }

        $subAccounts = $this->em->getRepository(Subaccount::class)->findBy(['accountid' => $account]);

        if (empty($subAccounts)) {
            return;
        }

        $this->logger->info(sprintf('account #%d, try detect credit card for subaccounts', $account->getId()));

        /** @var Subaccount $subacc */
        foreach ($subAccounts as $subacc) {
            $this->detectSubaccountCreditCard($subacc);
        }
    }

    private function detectSubaccountCreditCard(Subaccount $subAccount): void
    {
        $accountId = $subAccount->getAccountid()->getId();
        $subId = $subAccount->getId();
        // could be changed in SaveAccountProperties (old account auditor code)
        $this->em->refresh($subAccount);
        $subDisplayName = $subAccount->getDisplayname();
        $providerId = $subAccount->getAccountid()->getProviderid()->getId();
        $cardId = $this->matcher->identify($subDisplayName, $providerId);

        $this->logger->info(sprintf(
            'account #%d, subaccount #%d, provider #%d, display name: "%s", detected credit card #%s',
            $accountId,
            $subId,
            $providerId,
            $subDisplayName,
            $cardId ?? 'null'
        ));

        if (is_null($cardId)) {
            $card = null;
        } else {
            $card = $this->em->getRepository(CreditCard::class)->find($cardId);
            $currentCard = $subAccount->getCreditcard();

            if ($currentCard instanceof CreditCard && $currentCard->getId() === $cardId) {
                return;
            }
        }

        $subAccount->setCreditcard($card);
        $this->em->persist($subAccount);
        $this->em->flush();
    }
}
