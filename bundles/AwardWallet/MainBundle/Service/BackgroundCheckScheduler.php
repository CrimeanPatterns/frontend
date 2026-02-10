<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\DonotsendRepository;
use AwardWallet\MainBundle\Entity\Repositories\MobileDeviceRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\RefundEvent;
use AwardWallet\MainBundle\Event\UserEmailVerificationChangedEvent;
use AwardWallet\MainBundle\Event\UserNotificationPreferencesUpdatedEvent;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use AwardWallet\MainBundle\Event\UserPushDeviceChangedEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class BackgroundCheckScheduler implements EventSubscriberInterface
{
    public const ACTIVITY_SCORE_ON_EXPIRATION_DATE = -1;
    public const ACTIVITY_SCORE_ON_LAST_CHANGE_DATE = -2;
    public const ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE = -3;
    public const ACTIVITY_SCORE_ON_TRIP_DELAY = -4;
    public const ACTIVITY_SCORE_ON_ACCELERATED_UPDATE = 1;

    public const PRIORITY_ACCELERATED_UPDATE = 100;
    public const PRIORITY_DEFAULT = 5;

    // Background check periods (in hours)
    private const NO_BACKGROUND_CHECK = 100 * 365 * 24; // 100 years - effectively never check

    private Connection $connection;

    private LoggerInterface $logger;

    private Statement $updateStatement;

    private UsrRepository $userRepository;

    private DonotsendRepository $dnsRepository;

    private MobileDeviceRepository $mobileDeviceRepository;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        UsrRepository $userRepository,
        DonotsendRepository $dnsRepository,
        MobileDeviceRepository $mobileDeviceRepository
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->dnsRepository = $dnsRepository;
        $this->mobileDeviceRepository = $mobileDeviceRepository;
    }

    /**
     * @return array
     */
    public function schedule($accountId)
    {
        $accountNextCheckData = $this->getAccountNextCheck($accountId);

        if (empty($this->updateStatement)) {
            $this->updateStatement = $this->connection->prepare("
        UPDATE Account
        SET
            ActivityScore = :activityScore,
            QueueDate = :queueDate,
            NextCheckPriority = :priority
        WHERE  
            AccountID = :accountId");
        }

        $this->updateStatement->execute([
            "activityScore" => $accountNextCheckData['ActivityScore'],
            "queueDate" => date("Y-m-d H:i:s", $accountNextCheckData['NextCheckDate']),
            "priority" => $accountNextCheckData['Priority'],
            "accountId" => $accountId,
        ]);

        return $accountNextCheckData;
    }

    /**
     * ActivityScore shows with what interval (in hours) account should be checked in background.
     *
     * @static
     * @return int|array
     */
    public function getAccountNextCheck($accountID, $return = 'score')
    {
        $fields = $this->getQuery($accountID)->fetch(\PDO::FETCH_ASSOC);

        if ($return == 'details') {
            return $fields;
        }

        if (isset($fields['UserID'])) {
            $user = $this->userRepository->find($fields['UserID']);
        }

        // initial data
        $checkDates = [];

        if (!empty($fields) && isset($user)) {
            $freeUser = $user->isFree();
            $userEmailNotificationsDisabled = $this->isEmailNotificationsDeliveryDisabled($user);
            $userPushNotificationsDisabled = $this->isPushNotificationsDeliveryDisabled($user);

            if (
                ($userEmailNotificationsDisabled && $userPushNotificationsDisabled)
                || $freeUser
            ) {
                self::addCheckDate($checkDates, self::NO_BACKGROUND_CHECK);

                return array_pop($checkDates);
            }

            if (
                $fields['IsArchived'] == Account::ARCHIVED
                && (!in_array($fields['ErrorCode'], [ACCOUNT_INVALID_PASSWORD, ACCOUNT_QUESTION]) || $fields['ErrorCount'] < 10)
            ) {
                self::addCheckDate($checkDates, 90 * 24);

                return reset($checkDates);
            }

            switch ($fields['ErrorCode']) {
                case ACCOUNT_INVALID_PASSWORD:
                case ACCOUNT_QUESTION:
                    if ($fields['ErrorCount'] == 0) {
                        self::addCheckDate($checkDates, 7 * 24);
                    }

                    if ($fields['ErrorCount'] > 0 && $fields['ErrorCount'] < 5) {
                        self::addCheckDate($checkDates, 31 * 24);
                    }

                    if ($fields['ErrorCount'] >= 5 && $fields['ErrorCount'] < 10) {
                        self::addCheckDate($checkDates, 90 * 24);
                    }

                    if ($fields['ErrorCount'] >= 10) {
                        self::addCheckDate($checkDates, 3 * 365 * 24);
                    }

                    break;

                case ACCOUNT_LOCKOUT:
                    self::addCheckDate($checkDates, 60 * 24);

                    break;

                case ACCOUNT_PROVIDER_ERROR:
                    self::addCheckDate($checkDates, 2 * 24);

                    break;

                default:
                    self::addCheckDate($checkDates, 32 * 24);
                    $updateDate = null;

                    if (!empty($fields['UpdateDate'])) {
                        $updateDate = strtotime($fields['UpdateDate']);
                    }
                    $nearestTripDate = null;

                    if (!empty($fields['NearestTripDate'])) {
                        $nearestTripDate = strtotime($fields['NearestTripDate']);
                    }
                    $expirationDate = null;

                    if (!empty($fields['ExpirationDate'])) {
                        $expirationDate = strtotime($fields['ExpirationDate']);
                    }
                    $lastChangeDate = null;

                    if (!empty($fields['LastChangeDate'])) {
                        $lastChangeDate = strtotime($fields['LastChangeDate']);
                    }
                    // default, once a month
                    self::addCheckDate($checkDates, 31 * 24, $updateDate);

                    if ($fields['BalanceChanges'] > 1 || $fields['Age'] <= 180 || $fields['FutureItCount'] > 0) {
                        self::addCheckDate($checkDates, 7 * 24, $updateDate);
                    }

                    $isRestaurant = $fields['Kind'] && intval($fields['Kind']) === PROVIDER_KIND_DINING;

                    if ($fields['FutureItCount'] > 0 && $fields['BalanceChanges'] >= 5 && ACCOUNT_LEVEL_AWPLUS == $fields['AccountLevel'] && !$isRestaurant) {
                        self::addCheckDate($checkDates, 24, $updateDate);
                    }

                    if ($fields['FutureItCount'] > 0 && $fields['BalanceChanges'] >= 10 && ACCOUNT_LEVEL_AWPLUS == $fields['AccountLevel'] && !$isRestaurant) {
                        self::addCheckDate($checkDates, 12, $updateDate);
                    }

                    // check in 2 days after expiration date, because it's possible that balance will drop near zero or change somehow. refs #6741
                    if (isset($expirationDate)
                        && $fields['ExpirationAutoSet'] == 1
                        && $expirationDate > $updateDate
                    ) {
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_EXPIRATION_DATE, null, $expirationDate + 2 * SECONDS_PER_DAY);
                    }

                    // check 1 day before expiration date, may be it is changed
                    if (isset($expirationDate)
                        && $expirationDate > $updateDate
                    ) {
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_EXPIRATION_DATE, null, $expirationDate - 8 * SECONDS_PER_DAY);
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_EXPIRATION_DATE, null, $expirationDate - 31 * SECONDS_PER_DAY);
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_EXPIRATION_DATE, null, $expirationDate - 61 * SECONDS_PER_DAY);
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_EXPIRATION_DATE, null, $expirationDate - 91 * SECONDS_PER_DAY);
                    }

                    // check in 6 hours if balance dropped to zero
                    if ((($fields['Balance'] != '' && abs($fields['Balance']) < 0.01 && $fields['LastBalance'] > 0) || $fields['DroppedToZeroCount'] > 0)
                        && isset($lastChangeDate) && isset($updateDate)
                        && ($updateDate - $lastChangeDate < 6 * SECONDS_PER_HOUR)
                    ) {
                        self::addCheckDate($checkDates, 6, $updateDate);
                    }

                    // check account not later than 3 hours before the first segment in a trip, refs #6727
                    if (isset($nearestTripDate)) {
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE, null, $nearestTripDate - 3 * SECONDS_PER_HOUR, 4);
                    }

                    // check qantas 3 and 5 days after last trip
                    if (!empty($fields['LastTripDate'])) {
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE, null, strtotime($fields['LastTripDate']) + 3 * DateTimeUtils::SECONDS_PER_DAY, 4);
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE, null, strtotime($fields['LastTripDate']) + 5 * DateTimeUtils::SECONDS_PER_DAY, 4);
                    }

                    // check account each hour before departure in case of flight delay
                    if (!empty($fields['NearestDelay'])) {
                        $scheduledDate = strtotime($fields["NearestDelay"]);

                        if (!empty($updateDate) && $updateDate > $scheduledDate) {
                            $scheduledDate = $updateDate;
                        }
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_TRIP_DELAY, null, $scheduledDate + SECONDS_PER_HOUR, 4);
                    }

                    $isBalanceWatch = !empty($fields['BalanceWatchStartDate'])
                        && ($fields['BalanceWatchStartDate'] >= date('Y-m-d H:i:s', strtotime('-24 hour'))
                        && $fields['BalanceWatchStartDate'] <= date('Y-m-d H:i:s'));

                    // check American Airlines(AAdvantage) accounts once a week, refs #6768
                    if ($fields['ProviderID'] == 1 && !$isBalanceWatch) {
                        if (isset($updateDate)) {
                            $baseDate = $updateDate;
                        } else {
                            $baseDate = time();
                        }

                        return [
                            'NextCheckDate' => $baseDate + 7 * SECONDS_PER_DAY,
                            'ActivityScore' => 7 * 24,
                            'Priority' => self::PRIORITY_DEFAULT,
                        ];
                    }

                    // account balance watch
                    // actually update will never fire in CheckBalancesCommand, priority is too low
                    // update will be done by BalanceWatchCommand
                    if ($isBalanceWatch) {
                        if (empty($fields['UpdateDate'])) {
                            $nextCheckDate = time();
                        } else {
                            $nextCheckDate = strtotime("+1 hour", strtotime($fields['UpdateDate']));
                        }
                        self::addCheckDate($checkDates, self::ACTIVITY_SCORE_ON_ACCELERATED_UPDATE, null, $nextCheckDate, self::PRIORITY_ACCELERATED_UPDATE);
                    }
            }
        } else {
            $this->addCheckDate($checkDates, 32 * 24);
        }

        // select nearest check date
        return $checkDates[min(array_keys($checkDates))];
    }

    public function onUserNotificationPreferencesUpdated(UserNotificationPreferencesUpdatedEvent $event)
    {
        $this->scheduleAccountsByUser($event->getUser());
    }

    public function onUserEmailVerificationChanged(UserEmailVerificationChangedEvent $event)
    {
        $this->scheduleAccountsByUser($event->getUser());
    }

    public function onUserPushDeviceChanged(UserPushDeviceChangedEvent $event)
    {
        $this->scheduleAccountsByUser($event->getUser());
    }

    public function onUserPlusChanged(UserPlusChangedEvent $event)
    {
        $user = $this->userRepository->find($event->getUserId());

        if ($user) {
            $this->scheduleAccountsByUser($user);
        }
    }

    public function onRefundEvent(RefundEvent $event)
    {
        $cart = $event->getCart();

        if ($cart->isAwPlus() && $cart->getUser()) {
            $this->scheduleAccountsByUser($cart->getUser());
        }
    }

    public function scheduleAccountsByUser(Usr $user): void
    {
        $stmt = $this->connection->executeQuery('
            SELECT AccountID 
            FROM Account 
            WHERE 
                UserID = ?
                AND ProviderID IS NOT NULL
                AND State <> ?
            ',
            [$user->getId(), ACCOUNT_DISABLED],
        );

        while ($row = $stmt->fetchAssociative()) {
            $this->schedule($row['AccountID']);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            UserNotificationPreferencesUpdatedEvent::class => ['onUserNotificationPreferencesUpdated'],
            UserEmailVerificationChangedEvent::class => ['onUserEmailVerificationChanged'],
            UserPushDeviceChangedEvent::class => ['onUserPushDeviceChanged'],
            UserPlusChangedEvent::NAME => ['onUserPlusChanged'],
            RefundEvent::NAME => ['onRefundEvent'],
        ];
    }

    private function isEmailNotificationsDeliveryDisabled(Usr $user): bool
    {
        if ($user->getEmailverified() == EMAIL_NDR) {
            return true;
        }

        if (
            $user->getEmailrewards() == REWARDS_NOTIFICATION_NEVER
            && $user->getEmailexpiration() == Usr::EMAIL_EXPIRATION_NEVER
            && !$user->getEmailnewplans()
            && !$user->getEmailplanschanges()
        ) {
            return true;
        }

        $dns = $this->dnsRepository->findOneBy(['email' => $user->getEmail()]);

        if (!is_null($dns)) {
            return true;
        }

        return false;
    }

    private function isPushNotificationsDeliveryDisabled(Usr $user): bool
    {
        if ($user->isWpDisableAll() && $user->isMpDisableAll()) {
            return true;
        }

        /** @var MobileDevice[] $devices */
        $devices = $this->mobileDeviceRepository->findBy([
            'userId' => $user,
            'tracked' => true,
        ]);

        $desktopDevices = [];
        $mobileDevices = [];

        foreach ($devices as $device) {
            if ($device->isDesktop()) {
                $desktopDevices[] = $device;
            } elseif ($device->isMobile()) {
                $mobileDevices[] = $device;
            }
        }

        if (empty($desktopDevices) && empty($mobileDevices)) {
            return true;
        }

        $desktopDisabled =
            empty($desktopDevices)
            || $user->isWpDisableAll()
            || (
                !$user->isWpRewardsActivity()
                && !$user->isWpExpire()
                && !$user->isWpNewPlans()
                && !$user->isWpPlanChanges()
            );
        $mobileDisabled =
            empty($mobileDevices)
            || $user->isMpDisableAll()
            || (
                !$user->isMpRewardsActivity()
                && !$user->isMpExpire()
                && !$user->isMpNewPlans()
                && !$user->isMpPlanChanges()
            );

        if ($desktopDisabled && $mobileDisabled) {
            return true;
        }

        return false;
    }

    /**
     * @param null  $baseDate
     * @param null  $nextCheckDate
     * @param int   $priority (lowest on top)
     */
    private function addCheckDate(array &$checkDates, $activityScore, $baseDate = null, $nextCheckDate = null, $priority = 5)
    {
        if (empty($baseDate)) {
            $baseDate = time();
        }

        if (empty($nextCheckDate)) {
            $nextCheckDate = $baseDate + $activityScore * SECONDS_PER_HOUR;
        }

        if ($nextCheckDate <= 0) {
            $nextCheckDate = $baseDate + 31 * SECONDS_PER_DAY;
        }

        $minDelay = 5;

        if ($activityScore == self::ACTIVITY_SCORE_ON_TRIP_DELAY || $activityScore == self::ACTIVITY_SCORE_ON_ACCELERATED_UPDATE) {
            $minDelay = null;
        }

        if ($minDelay !== null && $nextCheckDate <= (time() + SECONDS_PER_HOUR * $minDelay)) {
            // DieTrace("nextCheckDate too near, ignored", false);
            return;
        }

        $checkDates[$nextCheckDate] = [
            'NextCheckDate' => $nextCheckDate,
            'ActivityScore' => $activityScore,
            'Priority' => $priority,
        ];
    }

    private function getQuery(int $accountId)
    {
        $now = new \DateTime();

        return $this->connection->executeQuery(
            "
            SELECT
   				a.AccountID,
   				DateDiff(:now, a.CreationDate) AS Age,
   				a.UpdateDate,
   				a.LastChangeDate,
   				a.Balance,
   				a.LastBalance,
   				a.BalanceWatchStartDate,
   				subacc.DroppedToZeroCount,
   				(CASE WHEN a.ExpirationDate > ADDDATE(:now, INTERVAL -2 DAY) THEN
                        (CASE   WHEN subacc.ExpirationDate IS NOT NULL THEN LEAST(subacc.ExpirationDate, a.ExpirationDate)
                                ELSE a.ExpirationDate 
                        END)
                      ELSE NULL 
                END) AS ExpirationDate,
   				a.ExpirationAutoSet,
   				a.ProviderID,
   				p.Kind,
   				COALESCE(ab.BalanceChanges, 0) AS BalanceChanges,
   				GREATEST(
   				    COALESCE(HistoryCount, 0), 
   				    :rentalsCount + :reservationCount + :restaurantCount + :tripCount
                ) AS FutureItCount,
   				:nearestTripDate AS NearestTripDate,
   				:lastTripDate AS LastTripDate,
   				:nearestDelay AS NearestDelay,
   				u.AccountLevel,
   				a.ErrorCode,
   				a.ErrorCount,
                a.IsArchived,
                a.UserID
   			FROM Account a
   			LEFT JOIN Usr u ON a.UserID = u.UserID
   			LEFT JOIN Provider p ON p.ProviderID = a.ProviderID
   			LEFT OUTER JOIN (
   				SELECT 
   				    AccountID,
   					COUNT(*) AS BalanceChanges
   				FROM AccountBalance
   				WHERE
   				    UpdateDate > ADDDATE(:now, INTERVAL -12 MONTH)
   					AND AccountID = :accountId
   				GROUP BY AccountID
   			) ab ON ab.AccountID = a.AccountID
   			LEFT OUTER JOIN (
   				SELECT 
   				    AccountID,
   					COUNT(*) AS HistoryCount
   				FROM AccountHistory
   				WHERE PostingDate > ADDDATE(:now, INTERVAL -1 YEAR) AND Miles <> 0 AND AccountID = :accountId
   				GROUP BY AccountID
   			) hist ON hist.AccountID = a.AccountID
   			LEFT OUTER JOIN (
   			    SELECT
   			        AccountID,
   			        SUM(CASE WHEN (Balance = 0 AND LastBalance > 0) THEN 1 ELSE 0 END) as DroppedToZeroCount,
   			        MIN(CASE WHEN ExpirationDate > ADDDATE(:now, INTERVAL -2 DAY) THEN ExpirationDate ELSE NULL END) AS ExpirationDate
   			    FROM SubAccount
   			    WHERE AccountID = :accountId
   			    GROUP BY AccountID
   			) subacc ON subacc.AccountID = a.AccountID
   			WHERE
   				a.AccountID = :accountId
            ",
            [
                ':accountId' => $accountId,
                ':now' => $now->format('Y-m-d H:i:s'),
                ':rentalsCount' => $this->getFutureRentalsCount($accountId, $now),
                ':reservationCount' => $this->getFutureReservationsCount($accountId, $now),
                ':restaurantCount' => $this->getFutureRestaurantsCount($accountId, $now),
                ':tripCount' => $this->getFutureTripsCount($accountId, $now),
                ':nearestTripDate' => $this->getNearestTripDate($accountId, $now),
                ':lastTripDate' => $this->getLastTripDate($accountId, $now),
                ':nearestDelay' => $this->getNearestDelay($accountId, $now),
            ],
            [
                ':accountId' => \PDO::PARAM_INT,
                ':now' => \PDO::PARAM_STR,
                ':rentalsCount' => \PDO::PARAM_INT,
                ':reservationCount' => \PDO::PARAM_INT,
                ':restaurantCount' => \PDO::PARAM_INT,
                ':tripCount' => \PDO::PARAM_INT,
                ':nearestTripDate' => \PDO::PARAM_STR,
                ':lastTripDate' => \PDO::PARAM_STR,
                ':nearestDelay' => \PDO::PARAM_STR,
            ]
        );
    }

    private function getFutureRentalsCount(int $accountId, \DateTime $now): int
    {
        return stmtAssoc($this->connection->executeQuery("
                SELECT
                    r.PickupDateTime,
                    g.TimeZoneLocation
                FROM
                    Rental r
                    LEFT JOIN GeoTag g ON r.PickupGeoTagID = g.GeoTagID
                WHERE
                    r.AccountID = :accountId    
                    AND r.Hidden = 0 
                    AND r.PickupDateTime > :now - INTERVAL 12 HOUR
                    AND r.PickupDateTime <= :now + INTERVAL 14 HOUR
            ", [
            ':accountId' => $accountId,
            ':now' => $now->format('Y-m-d H:i:s'),
        ], [
            ':accountId' => \PDO::PARAM_INT,
            ':now' => \PDO::PARAM_STR,
        ]))
            ->filter(function ($row) use ($now) {
                try {
                    $tz = new \DateTimeZone($row['TimeZoneLocation']);
                } catch (\Exception $e) {
                    $tz = new \DateTimeZone('UTC');
                }
                $date = new \DateTime($row['PickupDateTime'], $tz);

                return $date > $now;
            })->count()
            +
            $this->connection->executeQuery("
                SELECT
                    COUNT(*) AS Count
                FROM
                    Rental
                WHERE
                    AccountID = :accountId    
                    AND Hidden = 0
                    AND PickupDateTime > :now + INTERVAL 14 HOUR
            ", [
                ':accountId' => $accountId,
                ':now' => $now->format('Y-m-d H:i:s'),
            ], [
                ':accountId' => \PDO::PARAM_INT,
                ':now' => \PDO::PARAM_STR,
            ])->fetchColumn();
    }

    private function getFutureReservationsCount(int $accountId, \DateTime $now): int
    {
        return stmtAssoc($this->connection->executeQuery("
                SELECT
                    r.CheckInDate,
                    g.TimeZoneLocation
                FROM
                    Reservation r
                    LEFT JOIN GeoTag g ON r.GeoTagID = g.GeoTagID
                WHERE
                    r.AccountID = :accountId    
                    AND r.Hidden = 0
                    AND r.CheckInDate > :now - INTERVAL 12 HOUR
                    AND r.CheckInDate <= :now + INTERVAL 14 HOUR
            ", [
            ':accountId' => $accountId,
            ':now' => $now->format('Y-m-d H:i:s'),
        ], [
            ':accountId' => \PDO::PARAM_INT,
            ':now' => \PDO::PARAM_STR,
        ]))
            ->filter(function ($row) use ($now) {
                try {
                    $tz = new \DateTimeZone($row['TimeZoneLocation']);
                } catch (\Exception $e) {
                    $tz = new \DateTimeZone('UTC');
                }
                $date = new \DateTime($row['CheckInDate'], $tz);

                return $date > $now;
            })->count()
            +
            $this->connection->executeQuery("
                SELECT
                    COUNT(*) AS Count
                FROM
                    Reservation
                WHERE
                    AccountID = :accountId    
                    AND Hidden = 0
                    AND CheckInDate > :now + INTERVAL 14 HOUR
            ", [
                ':accountId' => $accountId,
                ':now' => $now->format('Y-m-d H:i:s'),
            ], [
                ':accountId' => \PDO::PARAM_INT,
                ':now' => \PDO::PARAM_STR,
            ])->fetchColumn();
    }

    private function getFutureRestaurantsCount(int $accountId, \DateTime $now): int
    {
        return stmtAssoc($this->connection->executeQuery("
                SELECT
                    r.StartDate,
                    g.TimeZoneLocation
                FROM
                    Restaurant r
                    LEFT JOIN GeoTag g ON r.GeoTagID = g.GeoTagID
                WHERE
                    r.AccountID = :accountId    
                    AND r.Hidden = 0
                    AND r.StartDate > :now - INTERVAL 12 HOUR
                    AND r.StartDate <= :now + INTERVAL 14 HOUR
            ", [
            ':accountId' => $accountId,
            ':now' => $now->format('Y-m-d H:i:s'),
        ], [
            ':accountId' => \PDO::PARAM_INT,
            ':now' => \PDO::PARAM_STR,
        ]))
            ->filter(function ($row) use ($now) {
                try {
                    $tz = new \DateTimeZone($row['TimeZoneLocation']);
                } catch (\Exception $e) {
                    $tz = new \DateTimeZone('UTC');
                }
                $date = new \DateTime($row['StartDate'], $tz);

                return $date > $now;
            })->count()
            +
            $this->connection->executeQuery("
                SELECT
                    COUNT(*) AS Count
                FROM
                    Restaurant
                WHERE
                    AccountID = :accountId    
                    AND Hidden = 0
                    AND StartDate > :now + INTERVAL 14 HOUR
            ", [
                ':accountId' => $accountId,
                ':now' => $now->format('Y-m-d H:i:s'),
            ], [
                ':accountId' => \PDO::PARAM_INT,
                ':now' => \PDO::PARAM_STR,
            ])->fetchColumn();
    }

    private function getFutureTripsCount(int $accountId, \DateTime $now): int
    {
        return stmtAssoc($this->connection->executeQuery("
                SELECT
                    ts.DepDate,
                    g.TimeZoneLocation
                FROM
                    Trip t
                    JOIN TripSegment ts ON ts.TripID = t.TripID
                    LEFT JOIN GeoTag g ON ts.DepGeoTagID = g.GeoTagID
                WHERE
                    t.AccountID = :accountId    
                    AND t.Hidden = 0
                    AND ts.Hidden = 0
                    AND ts.DepDate > :now - INTERVAL 12 HOUR
                    AND ts.DepDate <= :now + INTERVAL 14 HOUR
            ", [
            ':accountId' => $accountId,
            ':now' => $now->format('Y-m-d H:i:s'),
        ], [
            ':accountId' => \PDO::PARAM_INT,
            ':now' => \PDO::PARAM_STR,
        ]))
            ->filter(function ($row) use ($now) {
                try {
                    $tz = new \DateTimeZone($row['TimeZoneLocation']);
                } catch (\Exception $e) {
                    $tz = new \DateTimeZone('UTC');
                }
                $date = new \DateTime($row['DepDate'], $tz);

                return $date > $now;
            })->count()
            +
            $this->connection->executeQuery("
                SELECT
                    COUNT(*) AS Count
                FROM
                    Trip t
                    JOIN TripSegment ts ON ts.TripID = t.TripID
                WHERE
                    t.AccountID = :accountId    
                    AND t.Hidden = 0
                    AND ts.Hidden = 0
                    AND ts.DepDate > :now + INTERVAL 14 HOUR
            ", [
                ':accountId' => $accountId,
                ':now' => $now->format('Y-m-d H:i:s'),
            ], [
                ':accountId' => \PDO::PARAM_INT,
                ':now' => \PDO::PARAM_STR,
            ])->fetchColumn();
    }

    private function getNearestTripDate(int $accountId, \DateTime $now): ?string
    {
        $q = $this->connection->executeQuery("
                SELECT
                    ts.DepDate,
                    g.TimeZoneLocation
                FROM
                    Trip t
                    JOIN TripSegment ts ON ts.TripID = t.TripID
                    LEFT JOIN GeoTag g ON ts.DepGeoTagID = g.GeoTagID
                WHERE
                    t.AccountID = :accountId    
                    AND t.Hidden = 0
                    AND ts.Hidden = 0
                    AND ts.DepDate > :now - INTERVAL 12 HOUR + INTERVAL 3 HOUR
                ORDER BY
                    ts.DepDate
            ",
            [':accountId' => $accountId, ':now' => $now->format('Y-m-d H:i:s')],
            [':accountId' => \PDO::PARAM_INT, ':now' => \PDO::PARAM_STR]
        );
        $min = null;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            try {
                $tz = new \DateTimeZone($row['TimeZoneLocation']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }
            $date = new \DateTime($row['DepDate'], $tz);

            if ($date < $now) {
                continue;
            }

            if (!is_null($min) && $min->getTimestamp() + (3600 * 12) < $date->getTimestamp()) {
                break;
            }

            if (is_null($min) || $date < $min) {
                $min = $date;
            }
        }

        return $min ? $min->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null;
    }

    private function getLastTripDate(int $accountId, \DateTime $now): ?string
    {
        $q = $this->connection->executeQuery("
                SELECT
                    ts.ArrDate,
                    g.TimeZoneLocation
                FROM
                    Trip t
                    JOIN TripSegment ts ON ts.TripID = t.TripID
                    LEFT JOIN GeoTag g ON ts.ArrGeoTagID = g.GeoTagID
                WHERE
                    t.AccountID = :accountId    
                    AND t.Hidden = 0
                    AND ts.Hidden = 0
                    AND t.ProviderID = 33 /* qantas */
                    AND ts.ArrDate > :now - INTERVAL 6 DAY
                ORDER BY
                    ts.ArrDate DESC 
            ",
            [':accountId' => $accountId, ':now' => $now->format('Y-m-d H:i:s')],
            [':accountId' => \PDO::PARAM_INT, ':now' => \PDO::PARAM_STR]
        );
        $max = null;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            try {
                $tz = new \DateTimeZone($row['TimeZoneLocation']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }
            $date = new \DateTime($row['ArrDate'], $tz);

            if (!is_null($max) && $max->getTimestamp() - (3600 * 14) > $date->getTimestamp()) {
                break;
            }

            if (is_null($max) || $date > $max) {
                $max = $date;
            }
        }

        return $max ? $max->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null;
    }

    private function getNearestDelay(int $accountId, \DateTime $now): ?string
    {
        $q = $this->connection->executeQuery("
                SELECT
                    ts.DepDate,
                    ts.ScheduledDepDate,
                    g.TimeZoneLocation
                FROM
                    Trip t
                    JOIN TripSegment ts ON ts.TripID = t.TripID
                    JOIN GeoTag g ON ts.DepGeoTagID = g.GeoTagID
                WHERE
                    t.AccountID = :accountId    
                    AND t.Hidden = 0
                    AND ts.Hidden = 0
                    AND t.Cancelled = 0
                    AND ts.DepDate > ts.ScheduledDepDate
                    AND ts.DepDate > :now - INTERVAL 12 HOUR
                ORDER BY
                    ts.DepDate
            ",
            [':accountId' => $accountId, ':now' => $now->format('Y-m-d H:i:s')],
            [':accountId' => \PDO::PARAM_INT, ':now' => \PDO::PARAM_STR]
        );
        $minDepDate = null;
        $minScheduledDepDate = null;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            try {
                $tz = new \DateTimeZone($row['TimeZoneLocation']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }
            $depDate = new \DateTime($row['DepDate'], $tz);
            $scheduledDepDate = new \DateTime($row['ScheduledDepDate'], $tz);
            $diff = $scheduledDepDate->diff($depDate);

            if ($depDate < $now || $diff->days > 0) {
                continue;
            }

            if (!is_null($minDepDate) && $minDepDate->getTimestamp() + (3600 * 12) < $depDate->getTimestamp()) {
                break;
            }

            if (is_null($minDepDate) || $depDate < $minDepDate) {
                $minDepDate = $depDate;
                $minScheduledDepDate = $scheduledDepDate;
            }
        }

        return $minScheduledDepDate ? $minScheduledDepDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null;
    }
}
