<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillCardsCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:fill-cards';

    /** @var Connection */
    private $connection;
    /** @var Connection */
    private $replicaConnection;
    /** @var Logger */
    private $logger;
    /** @var CreditCardMatcher */
    private $matcher;
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        Logger $logger,
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        CreditCardMatcher $matcher,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->replicaConnection = $replicaUnbufferedConnection;
        $this->matcher = $matcher;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('cobrand', null, InputOption::VALUE_NONE)
            ->addOption('update', null, InputOption::VALUE_NONE)
            ->addOption('providerId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '', [Provider::CHASE_ID])
            ->addOption('cardId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $update = $input->getOption('update');

        $this->logger->info("preparing");
        $updateStmt = $this->connection->prepare(
            "UPDATE SubAccount SET CreditCardID = :CreditCardID WHERE SubAccountID = :SubAccountID"
        );

        $sql = "SELECT s.SubAccountID, s.AccountID, s.DisplayName, s.CreditCardID, a.ProviderID, a.UserID
                FROM SubAccount s JOIN Account a ON s.AccountID = a.AccountID
                WHERE a.ProviderID IN (:providerIds)
                ::FILTERS::";

        $filters = "";
        $params = [];
        $paramTypes = [];

        $params["providerIds"] = $input->getOption('providerId');
        $paramTypes["providerIds"] = Connection::PARAM_INT_ARRAY;

        if (!$update) {
            $filters .= "AND s.CreditCardID IS NULL AND a.SuccessCheckDate >= adddate(now(), -3)";
        }

        if ($input->getOption('cardId')) {
            $filters .= "AND s.CreditCardID in (:cardIds)";
            $params["cardIds"] = $input->getOption('cardId');
            $paramTypes["cardIds"] = Connection::PARAM_INT_ARRAY;
        }

        if ($input->getOption('userId')) {
            $filters .= "AND a.UserID in (:userIds)";
            $params["userIds"] = $input->getOption('userId');
            $paramTypes["userIds"] = Connection::PARAM_INT_ARRAY;
        }

        $sql = str_replace("::FILTERS::", $filters, $sql);

        $this->logger->info("loading subaccounts: $sql " . json_encode($params));
        $result = $this->replicaConnection->executeQuery(
            $sql,
            $params,
            $paramTypes
        );

        $dismissed = [];
        $updated = [];
        $timeToLog = time();
        $this->connection->beginTransaction();
        $count = 0;

        while ($subAcc = $result->fetch()) {
            $this->logger->pushProcessor(function ($record) use ($subAcc) {
                $record['context']['AccountID'] = $subAcc['AccountID'];
                $record['context']['UserID'] = $subAcc['UserID'];

                return $record;
            });

            try {
                if ($timeToLog + 30 < time()) {
                    $this->logger->info("SubAccount. " . (count($dismissed) + count($updated)) . " rows processed.");
                    $timeToLog = time();
                }

                $cardId = $this->matcher->identify($subAcc['DisplayName'], (int) $subAcc['ProviderID']);

                if ($cardId === null) {
                    $dismissed[] = $subAcc['DisplayName'] . " (ProviderID = {$subAcc['ProviderID']})";

                    if (!empty($subAcc['CreditCardID'])) {
                        $updateStmt->execute([":CreditCardID" => null, ":SubAccountID" => $subAcc["SubAccountID"]]);
                    }

                    continue;
                }

                if ($cardId !== (int) $subAcc['CreditCardID']) {
                    $updateStmt->execute([":CreditCardID" => $cardId, ":SubAccountID" => $subAcc["SubAccountID"]]);
                    $this->logger->info("SubAccount {$subAcc["SubAccountID"]} was updated, CardID = $cardId");
                    $updated[] = $subAcc["SubAccountID"];
                }

                $count++;

                if (($count % 100) === 0) {
                    $this->connection->commit();
                    $this->connection->beginTransaction();
                }
            } finally {
                $this->logger->popProcessor();
            }
        }

        $this->connection->commit();

        $this->logger->notice("Processed: $count");
        $this->logger->notice("Updated Total: " . count($updated));
        $this->logger->notice("Unknown Total: " . count($dismissed));

        return 0;
    }

    private function detectViaSubAcc(array $subAcc, array $patterns)
    {
        $providerId = (int) $subAcc['ProviderID'];
        $name = $subAcc['DisplayName'];

        foreach ($patterns[$providerId] as $card) {
            foreach ($card["patterns"] as $pattern) {
                if (empty($pattern)) {
                    continue;
                }
                $isPreg = substr($pattern, 0, 1) === '#' ? true : false;
                $match = $isPreg ? preg_match($pattern, $name) === 1 : stripos($name, trim($pattern)) !== false;

                if ($match) {
                    return $card["id"];
                }
            }
        }
    }

    private function completeCobrandSubAccounts(bool $update)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from(CreditCard::class, 'c')
            ->where($qb->expr()->andX(
                $qb->expr()->isNotNull('c.cobrandProvider'),
                $qb->expr()->isNotNull('c.cobrandSubAccPatterns')
            ))
            ->orderBy('c.cobrandProvider', 'ASC');

        $cards = $qb->getQuery()->execute();
        $patterns = [];

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            // собираем regexp для дальнейшего матчинга карт
            $providerId = $card->getCobrandProvider()->getProviderid();

            if (!isset($patterns[$providerId])) {
                $patterns[$providerId] = [];
            }

            $patterns[$providerId][] = [
                'id' => $card->getId(),
                'patterns' => explode("\n", $card->getCobrandSubAccPatterns()),
            ];
        }

        $cobrandIds = $this->connection->executeQuery(
            "select distinct(CobrandProviderID) from CreditCard where CobrandProviderID is not null"
        )->fetchAll();

        $sql = "SELECT s.SubAccountID, s.AccountID, s.DisplayName, s.CreditCardID, a.ProviderID
                FROM SubAccount s JOIN Account a ON s.AccountID = a.AccountID
                WHERE a.ProviderID IN (?)
                ::SUBACCOUNT_CHECK::";

        $sql = str_replace("::SUBACCOUNT_CHECK::", $update ? "" : "AND s.CreditCardID IS NULL AND a.SuccessCheckDate >= adddate(now(), -3)", $sql);

        $this->logger->info("loading subaccounts");
        $result = $this->replicaConnection->executeQuery(
            $sql,
            [$cobrandIds],
            [Connection::PARAM_INT_ARRAY]
        );

        while ($subAcc = $result->fetch()) {
            $ccId = $this->detectViaSubAcc($subAcc, $patterns);
        }
    }
}
