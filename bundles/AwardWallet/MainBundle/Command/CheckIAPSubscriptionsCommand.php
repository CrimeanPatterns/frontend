<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIAPSubscriptionsCommand extends Command
{
    protected static $defaultName = 'aw:iap:check-subscriptions';

    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private AppleProvider $appleProvider;
    private GooglePlay $googleProvider;
    private Billing $billing;
    private ExpirationCalculator $expirationCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        AppleProvider $appleProvider,
        GooglePlay $googleProvider,
        Billing $billing,
        ExpirationCalculator $expirationCalculator
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->appleProvider = $appleProvider;
        $this->googleProvider = $googleProvider;
        $this->billing = $billing;
        $this->expirationCalculator = $expirationCalculator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check iap subscription, upgrade aw users, refunds')
            ->addArgument('platform', InputArgument::REQUIRED, 'mobile platform: ios or android')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('startUserId', 's', InputOption::VALUE_REQUIRED, 'filter by userId >= startUserId')
            ->addOption('endUserId', 'i', InputOption::VALUE_REQUIRED, 'filter by userId <= endUserId')
            ->addOption('startDate', 'd', InputOption::VALUE_OPTIONAL, 'start purchase date')
            ->addOption('sleep', 'l', InputOption::VALUE_OPTIONAL, 'pauses between requests to the server Apple/Google (in micro seconds)', 0)
            ->addOption('disable-date-filter', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $conn = $em->getConnection();
        $logger = $this->logger;
        $platform = strtolower($input->getArgument('platform'));

        if (!in_array($platform, ['ios', 'android'])) {
            throw new \InvalidArgumentException('Wrong platform. Must be ios or android');
        }

        $logger->info(sprintf('mobile platform: [%s]', $platform));

        $filter = '';
        $params = [
            $platform == 'ios' ? Cart::PAYMENTTYPE_APPSTORE : Cart::PAYMENTTYPE_ANDROIDMARKET,
            [AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE],
        ];
        $types = [
            \PDO::PARAM_INT,
            Connection::PARAM_INT_ARRAY,
        ];

        if ($platform == 'ios') {
            $filter .= ' AND u.IosReceipt IS NOT NULL';
        } else {
            $filter .= ' AND c.PurchaseToken IS NOT NULL';
        }

        $usersIds = $input->getOption('userId');

        if ($usersIds) {
            $usersIds = array_map('intval', $usersIds);
            $logger->info(sprintf('filter by userId: [%s]', implode(', ', $usersIds)));
            $filter .= ' AND c.UserID IN (?)';
            $params[] = $usersIds;
            $types[] = Connection::PARAM_INT_ARRAY;
        }

        $startUserId = $input->getOption('startUserId');

        if ($startUserId) {
            $startUserId = intval($startUserId);
            $logger->info(sprintf('filter by userId >= startUserId: [%d]', $startUserId));
            $filter .= ' AND c.UserID >= ?';
            $params[] = $startUserId;
            $types[] = \PDO::PARAM_INT;
        }

        $endUserId = $input->getOption('endUserId');

        if ($endUserId) {
            $endUserId = intval($endUserId);
            $logger->info(sprintf('filter by userId <= endUserId: [%d]', $endUserId));
            $filter .= ' AND c.UserID <= ?';
            $params[] = $endUserId;
            $types[] = \PDO::PARAM_INT;
        }

        $startDate = $input->getOption('startDate');

        if ($startDate) {
            $startDate = new \DateTime($startDate);
            $logger->info(sprintf('start date: [%s]', $dateParam = $startDate->format('Y-m-d H:i:s')));
            $payDateFilter = '(u.AccountLevel = ? AND u.PlusExpirationDate <= NOW() + INTERVAL 1 DAY AND u.PlusExpirationDate > NOW() - INTERVAL 7 DAY) OR c.PayDate > ?';
            $params[] = ACCOUNT_LEVEL_AWPLUS;
            $types[] = \PDO::PARAM_INT;
            $params[] = $dateParam;
            $types[] = \PDO::PARAM_STR;
        } elseif ($input->getOption('disable-date-filter')) {
            $logger->info('skipping date filter, will check all users with mobile subscription');
            $payDateFilter = 'u.Subscription = ' . Usr::SUBSCRIPTION_MOBILE;
        } else {
            // ios
            // You can't request refunds for recurring charges
            // If you live in the EU, then you should be able to get a refund within 14 days of purchase, no questions asked.
            // If you live elsewhere, or it has been more than two weeks since your purchase, then your request might not be granted without a legitimate reason.

            // android
            // https://support.google.com/googleplay/answer/2479637?hl=en - 2 days
            // https://support.google.com/googleplay/answer/7659581 after March 28, 2018 - 14 days

            $payDateFilter = '(u.AccountLevel = ? AND u.PlusExpirationDate <= NOW() + INTERVAL 1 DAY AND u.PlusExpirationDate > NOW() - INTERVAL 7 DAY) OR (
                /* 15 days after purchase, if he cancelled just after purchasing */
                c.PayDate > NOW() - INTERVAL 15 DAY
                /* every day 1 week after missing expected billing cycle */
                OR (c.PayDate <= NOW() - INTERVAL 1 YEAR AND c.PayDate > NOW() - INTERVAL 1 YEAR - INTERVAL 1 WEEK)
                /* missing billing cycle, we want to find cancelled subscriptions */
                OR (u.Subscription = ' . Usr::SUBSCRIPTION_MOBILE . ' and u.LastSubscriptionCartItemID = ci.CartItemID and c.PayDate <= NOW() - INTERVAL 1 YEAR - INTERVAL 1 WEEK)
            )';
            $params[] = ACCOUNT_LEVEL_AWPLUS;
            $types[] = \PDO::PARAM_INT;
        }

        $sleep = intval($input->getOption('sleep'));
        $logger->info(sprintf('sleep: [%d micro seconds]', $sleep));

        if ($payDateFilter !== '') {
            $filter .= " AND ($payDateFilter)";
        }

        $stmt = $conn->executeQuery($sql = "
            SELECT   DISTINCT c.UserID
			FROM     Cart c
			         JOIN CartItem ci ON c.CartID = ci.CartID
			         JOIN Usr u ON u.UserID = c.UserID
			WHERE    c.PayDate IS NOT NULL
			         AND c.PaymentType = ?
			         AND ci.TypeID IN (?)
			         $filter
			ORDER BY c.UserID
        ", $params, $types);
        $logger->info(sprintf('sql: [%s]', $sql));

        $processed = $upgraded = $downgraded = 0;

        /** @var ProviderInterface $provider */
        $provider = $platform == 'ios'
            ? $this->appleProvider
            : $this->googleProvider;
        $billing = $this->billing;
        $userRep = $em->getRepository(Usr::class);
        $getPlusExpiration = function (Usr $user) {
            return $this->expirationCalculator->getAccountExpiration($user->getId())['date'];
        };

        while ($userId = $stmt->fetchOne()) {
            /** @var Usr $user */
            $user = $userRep->find($userId);

            if (!$user) {
                continue;
            }

            $beforePlusExpiration = null;

            if ($beforeHasPlus = $user->isAwPlus()) {
                $beforePlusExpiration = $getPlusExpiration($user);
            }
            $logger->info(sprintf(
                'check %s subscription, userId: %d (%s), %s',
                $platform,
                $user->getId(),
                $user->getFullName(),
                $beforeHasPlus ? 'aw plus expire: ' . date('Y-m-d', $beforePlusExpiration) : 'aw plus none'
            ));

            $provider->scanSubscriptions($user, $billing);
            $em->refresh($user);
            $afterPlusExpiration = null;

            if ($afterHasPlus = $user->isAwPlus()) {
                $afterPlusExpiration = $getPlusExpiration($user);
            }

            $logger->info(sprintf(
                'checking %s subscription result, userId: %d (%s), %s',
                $platform,
                $user->getId(),
                $user->getFullName(),
                $afterHasPlus ? 'aw plus expire: ' . date('Y-m-d', $afterPlusExpiration) : 'aw plus none'
            ), $context = [
                'beforeHasPlus' => $beforeHasPlus,
                'afterHasPlus' => $afterHasPlus,
                'beforePlusExpiration' => $beforeHasPlus ? date('Y-m-d', $beforePlusExpiration) : null,
                'afterPlusExpiration' => $afterHasPlus ? date('Y-m-d', $afterPlusExpiration) : null,
            ]);

            if ((!$beforeHasPlus && $afterHasPlus) || ($beforeHasPlus && $afterHasPlus && $beforePlusExpiration < $afterPlusExpiration)) {
                $logger->info(sprintf('userId: %d was upgraded', $user->getId()), $context);
                $upgraded++;
            } elseif ($beforeHasPlus && !$afterHasPlus) {
                $logger->info(sprintf('userId: %d was downgraded', $user->getId()), $context);
                $downgraded++;
            }

            $processed++;

            if ($sleep > 0) {
                usleep($sleep);
            }
        }

        $logger->info(sprintf('done, processed users: %d, upgraded: %d, downgraded: %d', $processed, $upgraded, $downgraded));

        return 0;
    }
}
