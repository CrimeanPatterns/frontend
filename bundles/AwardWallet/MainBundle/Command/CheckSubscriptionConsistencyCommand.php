<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\UserPurchase\History;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSubscriptionConsistencyCommand extends Command
{
    public static $defaultName = 'aw:check-subscription-consistency';

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private UsrRepository $usrRepository;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Connection $connection,
        UsrRepository $usrRepository
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->usrRepository = $usrRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check subscription consistency')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processed = 0;
        $inconsistent = 0;
        $sql = "
            SELECT
                UserID
            FROM
                Usr
            WHERE
                (
                    AccountLevel = :accountLevelPlus
                    OR (
                        AccountLevel = :accountLevelFree
                        AND (
                            PlusExpirationDate IS NOT NULL
                            OR AT201ExpirationDate IS NOT NULL
                            OR Subscription IS NOT NULL
                            OR SubscriptionType IS NOT NULL
                        )
                    )
                )
        ";

        if (!empty($userIds = $input->getOption('userId'))) {
            $userIds = array_map('intval', $userIds);

            $sql .= " AND UserID IN (" . implode(', ', $userIds) . ")";
        }
        $output->writeln(sprintf('sql: %s', $sql));

        $stmt = $this->connection->executeQuery($sql, [
            'accountLevelPlus' => ACCOUNT_LEVEL_AWPLUS,
            'accountLevelFree' => ACCOUNT_LEVEL_FREE,
        ]);
        $startTime = microtime(true);

        while ($userId = $stmt->fetchOne()) {
            /** @var Usr $user */
            $user = $this->usrRepository->find($userId);

            if (!$user) {
                continue;
            }

            $messages = [];
            $now = new \DateTime();
            $awPlusInfo = History::makeAwPlusInfo($user);
            $at201Info = History::makeAt201Info($user);
            $awPlusExpirationDate = $awPlusInfo->getCurrentExpirationDate($now);
            $at201ExpirationDate = $at201Info->getCurrentExpirationDate($now);
            $awPlusCurrentPeriod = $awPlusInfo->getCurrentPeriod($now);
            $at201CurrentPeriod = $at201Info->getCurrentPeriod($now);

            $dbAccountLevelFree = $user->getAccountlevel() == ACCOUNT_LEVEL_FREE;
            $dbAwPlusExpirationDate = $user->getPlusExpirationDate();
            $dbAt201ExpirationDate = $user->getAt201ExpirationDate();
            $dbSubscription = $user->getSubscription();
            $dbSubscriptionType = $user->getSubscriptionType();

            $userHasAwPlus = !is_null($awPlusExpirationDate) && $awPlusExpirationDate > $now;

            // check Usr.AccountLevel
            if ($userHasAwPlus && $dbAccountLevelFree) {
                $messages[] = 'no awplus';
            } elseif (!$dbAccountLevelFree && !$userHasAwPlus) {
                $messages[] = 'fake awplus';
            }

            // check Usr.PlusExpirationDate
            if (!is_null($awPlusExpirationDate) && is_null($dbAwPlusExpirationDate)) {
                $messages[] = sprintf('no awplus expiration date "%s"', $awPlusExpirationDate->format('Y-m-d'));
            } elseif (!is_null($dbAwPlusExpirationDate) && is_null($awPlusExpirationDate)) {
                $messages[] = sprintf('fake awplus expiration date "%s"', $dbAwPlusExpirationDate->format('Y-m-d'));
            } elseif (!is_null($awPlusExpirationDate) && !is_null($dbAwPlusExpirationDate)) {
                if ($awPlusCurrentPeriod->isGracePeriod()) {
                    $expDate = $awPlusCurrentPeriod->getStartDate();
                } else {
                    $expDate = $awPlusExpirationDate;
                }

                if ($expDate->format('Y-m-d') !== $dbAwPlusExpirationDate->format('Y-m-d')) {
                    $messages[] = sprintf(
                        'awplus expiration date "%s" != "%s"',
                        $awPlusExpirationDate->format('Y-m-d'),
                        $dbAwPlusExpirationDate->format('Y-m-d')
                    );
                }
            }

            // check Usr.At201ExpirationDate
            if (!is_null($at201ExpirationDate) && is_null($dbAt201ExpirationDate)) {
                $messages[] = sprintf('no at201 expiration date "%s"', $at201ExpirationDate->format('Y-m-d'));
            } elseif (!is_null($dbAt201ExpirationDate) && is_null($at201ExpirationDate)) {
                $messages[] = sprintf('fake at201 expiration date "%s"', $dbAt201ExpirationDate->format('Y-m-d'));
            } elseif (!is_null($at201ExpirationDate) && !is_null($dbAt201ExpirationDate)) {
                if ($at201CurrentPeriod->isGracePeriod()) {
                    $expDate = $at201CurrentPeriod->getStartDate();
                } else {
                    $expDate = $at201ExpirationDate;
                }

                if ($expDate->format('Y-m-d') !== $dbAt201ExpirationDate->format('Y-m-d')) {
                    $messages[] = sprintf(
                        'at201 expiration date "%s" != "%s"',
                        $at201ExpirationDate->format('Y-m-d'),
                        $dbAt201ExpirationDate->format('Y-m-d')
                    );
                }
            }

            // Check Usr.Subscription
            $userHasAwSubscription = !is_null($awPlusCurrentPeriod)
                && (
                    $awPlusCurrentPeriod->isGracePeriod()
                    || (
                        $awPlusCurrentPeriod->isActive()
                        && $awPlusCurrentPeriod->getCart()
                        && $awPlusCurrentPeriod->getCart()->isAwPlusSubscription()
                    )
                );
            $userHasAt201Subscription = !is_null($at201CurrentPeriod)
                && (
                    $at201CurrentPeriod->isGracePeriod()
                    || (
                        $at201CurrentPeriod->isActive()
                        && $at201CurrentPeriod->getCart()
                        && !is_null($at201CurrentPeriod->getCart()->getAT201Item())
                    )
                );
            $userHasSubscription = $userHasAwSubscription || $userHasAt201Subscription;

            if ($userHasSubscription && is_null($dbSubscription)) {
                $messages[] = 'no subscription';
            } elseif (!$userHasSubscription && !is_null($dbSubscription)) {
                $messages[] = 'fake subscription';
            }

            // check Usr.SubscriptionType
            if ($userHasAt201Subscription && $dbSubscriptionType != Usr::SUBSCRIPTION_TYPE_AT201) {
                $messages[] = is_null($dbSubscriptionType) ? 'no at201 subscription type' : 'not at201 subscription type';
            } elseif ($userHasAwSubscription && !$userHasAt201Subscription && $dbSubscriptionType != Usr::SUBSCRIPTION_TYPE_AWPLUS) {
                $messages[] = is_null($dbSubscriptionType) ? 'no awplus subscription type' : 'not awplus subscription type';
            } elseif (!$userHasSubscription && !is_null($dbSubscriptionType)) {
                $messages[] = 'fake subscription type';
            }

            if (\count($messages) > 0) {
                $inconsistent++;
                $this->logger->info(
                    sprintf(
                        '[inconsistent] %s, #%d: %s',
                        $user->getFullName(),
                        $user->getId(),
                        implode(', ', $messages)
                    ),
                    [
                        'DbAccountLevel' => $user->getAccountlevel(),
                        'DbAwPlusExpirationDate' => $dbAwPlusExpirationDate ? $dbAwPlusExpirationDate->format('Y-m-d') : null,
                        'DbAt201ExpirationDate' => $dbAt201ExpirationDate ? $dbAt201ExpirationDate->format('Y-m-d') : null,
                        'DbSubscription' => $dbSubscription,
                        'DbSubscriptionType' => $dbSubscriptionType,
                        'AwPlusHistory' => array_map('strval', $awPlusInfo->getCurrentAndNextPeriods($now)),
                        'AwPlusCurrent' => $awPlusCurrentPeriod ? strval($awPlusCurrentPeriod) : null,
                        'At201History' => array_map('strval', $at201Info->getCurrentAndNextPeriods($now)),
                        'At201Current' => $at201CurrentPeriod ? strval($at201CurrentPeriod) : null,
                        'AwPlusExpirationDate' => $awPlusExpirationDate ? $awPlusExpirationDate->format('Y-m-d') : null,
                        'At201ExpirationDate' => $at201ExpirationDate ? $at201ExpirationDate->format('Y-m-d') : null,
                        'HasAwSubscription' => $userHasAwSubscription,
                        'HasAt201Subscription' => $userHasAt201Subscription,
                    ]
                );
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->entityManager->clear();
                $now = microtime(true);
                $speed = round(100 / ($now - $startTime), 1);
                $this->logger->info("processed {$processed} users, mem: " . round(memory_get_usage(true) / 1024 / 1024, 1) . " Mb, speed: $speed u/s");
                $startTime = $now;
            }
        }

        $output->writeln(sprintf('processed %d users, %d inconsistent', $processed, $inconsistent));

        return 0;
    }
}
