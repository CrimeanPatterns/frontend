<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus6Months;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UpgradeBlogReaders;
use AwardWallet\MainBundle\Globals\Cart\CartUserInfo;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UpgradeReaders
{
    public const CONDITION_DATE = '-6 months';
    public const CONDITION_MIN_EARNING_SUM = 29;
    public const CONDITION_MIN_ACCOUNTS = 2;
    public const CONDITION_MIN_VISIT = 3;
    public const CONDITION_MIN_POST_READ = 2;
    public const CONDITION_MIN_TIME_IN_MINUTE = 14;

    public const CONDITION_MAX_EARNING_SUM = 0;

    public const CONDITION_PERCENT_FROM_REAL = 10;

    private LoggerInterface $logger;
    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private Mailer $mailer;
    private Manager $cartManager;
    private LocalizeService $localizer;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        Manager $cartManager,
        LocalizeService $localizer
    ) {
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('UpgradeBlogReaders: ');
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->cartManager = $cartManager;
        $this->localizer = $localizer;
    }

    public function execute(): ?bool
    {
        $this->closeOpenedTime();

        $dateStart = new \DateTime(self::CONDITION_DATE);
        $real = $this->fetchRealConditionUsers($dateStart);
        $realCount = count($real);
        $limitFakeUpgrade = (int) ($realCount / 100 * self::CONDITION_PERCENT_FROM_REAL);
        $this->logger->info('Upgrade Blog Readers Users, realCount=' . $realCount . ', fakeCount=' . $limitFakeUpgrade);

        if (!$realCount) {
            $this->logger->info('No REAL users found for free upgrade');

            return null;
        }

        $this->logger->info('Upgrade according to real criteria');
        $this->upgradeUsers($real);

        if (!$limitFakeUpgrade) {
            $this->logger->info('Limit fake users');

            return true;
        }

        $fakes = $this->fetchFakeConditionUsers($dateStart, $limitFakeUpgrade);

        if (!count($fakes)) {
            $this->logger->info('No FAKE users found for free upgrade');

            return true;
        }

        $this->logger->info('Upgrade by fictitious criteria');
        $this->upgradeUsers($fakes);

        return true;
    }

    private function closeOpenedTime(): void
    {
        $this->connection->query('
            UPDATE BlogUserReport
            SET OutTime = DATE_ADD(InTime, INTERVAL 3 MINUTE)
            WHERE
                    OutTime IS NULL
                AND InTime < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ');
    }

    private function fetchRealConditionUsers(\DateTime $dateStart): array
    {
        return $this->connection->fetchAll('
            SELECT
                    u.UserID,
                    SUM(qt.Earnings) AS _sumEarnings,
                    (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                    (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost,
                    (SELECT SUM(UNIX_TIMESTAMP(bup_tv.OutTime) - UNIX_TIMESTAMP(bup_tv.InTime)) / 60 FROM BlogUserReport bup_tv WHERE u.UserID = bup_tv.UserID AND bup_tv.OutTime IS NOT NULL) AS _timeVisit
            FROM Usr u
            JOIN QsTransaction qt ON (qt.UserID = u.UserID)
            WHERE
                   u.AccountLevel = :accountLevel
                AND u.Subscription IS NULL
                AND u.Accounts > :minAccounts
                AND qt.ClickDate >= :dateStart
                AND qt.Approvals = 1
            GROUP BY u.UserID
            HAVING (
                    _sumEarnings > :minSum
                AND _countReport > :minVisit
                AND _countPost > :minUniqPost
                AND _timeVisit > :minTime
            )
        ', [
            'accountLevel' => ACCOUNT_LEVEL_FREE,
            'minAccounts' => self::CONDITION_MIN_ACCOUNTS,
            'dateStart' => $dateStart->format('Y-m-d'),
            'minSum' => self::CONDITION_MIN_EARNING_SUM,
            'minVisit' => self::CONDITION_MIN_VISIT,
            'minUniqPost' => self::CONDITION_MIN_POST_READ,
            'minTime' => self::CONDITION_MIN_TIME_IN_MINUTE,
        ], [
            'accountLevel' => \PDO::PARAM_INT,
            'minAccounts' => \PDO::PARAM_INT,
            'dateStart' => \PDO::PARAM_STR,
            'minSum' => \PDO::PARAM_INT,
            'minVisit' => \PDO::PARAM_INT,
            'minTime' => \PDO::PARAM_INT,
        ]);
    }

    private function fetchFakeConditionUsers(\DateTime $dateStart, int $limitFakeUpgrade): array
    {
        return $this->connection->fetchAll('
            SELECT
                    u.UserID,
                    ROUND(SUM(qt.Earnings), 0) AS _sumEarnings,
                    (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                    (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost,
                    (SELECT SUM(UNIX_TIMESTAMP(bup_tv.OutTime) - UNIX_TIMESTAMP(bup_tv.InTime)) / 60 FROM BlogUserReport bup_tv WHERE u.UserID = bup_tv.UserID AND bup_tv.OutTime IS NOT NULL) AS _timeVisit
            FROM Usr u
            JOIN QsTransaction qt ON (qt.UserID = u.UserID)
            WHERE
                    u.AccountLevel = :accountLevel
                AND u.Subscription IS NULL
                AND u.Accounts > :minAccounts
                AND qt.ClickDate > :dateStart
            GROUP BY u.UserID
            HAVING (
                    _sumEarnings = :maxSum
            )
            ORDER BY _timeVisit DESC, _countReport DESC, u.UserID DESC
            LIMIT ' . $limitFakeUpgrade . '
        ', [
            'accountLevel' => ACCOUNT_LEVEL_FREE,
            'minAccounts' => self::CONDITION_MIN_ACCOUNTS,
            'dateStart' => $dateStart->format('Y-m-d'),
            'maxSum' => self::CONDITION_MAX_EARNING_SUM,
        ], [
            'accountLevel' => \PDO::PARAM_INT,
            'minAccounts' => \PDO::PARAM_INT,
            'dateStart' => \PDO::PARAM_STR,
            'maxSum' => \PDO::PARAM_INT,
        ]);
    }

    private function upgradeUsers(array $users): bool
    {
        $untilDate = new \DateTime(AwPlus6Months::DURATION);
        $userRepository = $this->entityManager->getRepository(Usr::class);

        foreach ($users as $user) {
            $usr = $userRepository->find($user['UserID']);

            $template = new UpgradeBlogReaders($usr);
            $template->blogpostCount = $user['_countPost'];
            $template->amountTimeSpent = (int) ($user['_timeVisit'] / 60);
            $template->untilDate = $this->localizer->formatDate($untilDate, 'long', $usr->getLocale());
            $message = $this->mailer->getMessageByTemplate($template);

            $cartUserInfo = new CartUserInfo($usr->getId(), $usr->getId(), true);
            $cart = $this->cartManager->createNewCart($cartUserInfo);
            $cart->setPaymenttype(null);
            $cart->addItem(new AwPlus6Months());
            $this->cartManager->markAsPayed($cart);

            $this->mailer->send($message);

            $this->logger->info('AwPlus6Months', [
                'userId' => $user['UserID'],
                'untilDate' => $untilDate->format('Y-m-d'),
            ]);
        }

        return true;
    }
}
