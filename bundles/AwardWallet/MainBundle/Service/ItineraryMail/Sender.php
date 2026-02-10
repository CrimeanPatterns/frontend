<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Repositories\ParkingRepository;
use AwardWallet\MainBundle\Entity\Repositories\RentalRepository;
use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Repositories\RestaurantRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\ReservationChanged;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\ReservationNew;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Service\ABTestManager;
use AwardWallet\MainBundle\Service\DelayedProducer;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Constants\WhiteList;
use AwardWallet\MainBundle\Service\ItineraryMail\Itinerary as MailItinerary;
use AwardWallet\MainBundle\Timeline\NoForeignFeesCardsQuery;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Sender
{
    public const NEW_RESERVATION = 'reservation_new';
    public const UPDATE_RESERVATION = 'reservation_changed';

    public const LOCK_PREFIX = 'its_sender';
    public const LOCK_TTL = 10; // sec
    public const DELAY = 60000; // msec

    protected LoggerInterface $logger;

    protected NoForeignFeesCardsQuery $noForeignFeesCardsQuery;
    protected EmailScannerApi $scannerApi;
    protected TripRepository $tripRep;
    protected RentalRepository $rentalRep;
    protected ReservationRepository $reservationRep;
    protected RestaurantRepository $restaurantRep;

    protected ParkingRepository $parkingRep;

    private Mailer $mailer;

    private Formatter $formatter;

    private EntityManagerInterface $em;

    private \Memcached $memcached;

    private DelayedProducer $delayedProducer;

    private AdManager $adManager;

    private ABTestManager $abTestManager;

    public function __construct(
        Mailer $mailer,
        Formatter $formatter,
        EntityManagerInterface $em,
        \Memcached $memcached,
        DelayedProducer $itNotificationDelayedProducer,
        LoggerInterface $emailLogger,
        AdManager $adManager,
        EmailScannerApi $scannerApi,
        NoForeignFeesCardsQuery $noForeignFeesCardsQuery,
        ABTestManager $abTestManager
    ) {
        $this->mailer = $mailer;
        $this->formatter = $formatter;
        $this->em = $em;
        $this->memcached = $memcached;
        $this->delayedProducer = $itNotificationDelayedProducer;
        $this->logger = $emailLogger;
        $this->adManager = $adManager;
        $this->scannerApi = $scannerApi;
        $this->noForeignFeesCardsQuery = $noForeignFeesCardsQuery;
        $this->abTestManager = $abTestManager;
        $this->tripRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Trip::class);
        $this->rentalRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Rental::class);
        $this->reservationRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class);
        $this->restaurantRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class);
        $this->parkingRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Parking::class);
    }

    /**
     * @param Mailer $mailer
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;
    }

    public function mailNewReservations(Usr $user, array $options = [])
    {
        if ($this->locked('add', $user->getUserid())) {
            return;
        }

        $this->lock('add', $user->getUserid());

        $where = [
            "t.UserID = " . $user->getUserid(),
            "t.Hidden = 0",
            "(t.AccountID IS NOT NULL OR t.ConfFields IS NOT NULL OR t.Modified = 0)",
            "[StartDate] > (NOW() - INTERVAL " . TRIPS_PAST_DAYS . " DAY)", // future reservation
            "t.MailDate IS NULL",
        ];

        if (isset($options['ttl'])) {
            $where[] = "t.CreateDate >= ADDDATE(NOW(), INTERVAL -" . $options['ttl'] . " SECOND)";
        }

        if (isset($options['filter'])) {
            $where[] = $options['filter'];
        }

        $sql = $this->tripRep->TripsSQL($where)
            . "\nUNION\n" . $this->rentalRep->RentalsSQL($where)
            . "\nUNION\n" . $this->reservationRep->ReservationsSQL($where)
            . "\nUNION\n" . $this->restaurantRep->RestaurantsSQL($where)
//            ."\nUNION\n".$this->parkingRep->ParkingsSQL($where)
            . " ORDER BY StartDate";

        $conn = $this->em->getConnection();
        $tasks = $this->getTasks($user, $this->getDetails($sql), self::NEW_RESERVATION);
        $this->logger->info(sprintf('tasks count: %d for user %d', count($tasks), $user->getId()));

        foreach ($tasks as $task) {
            $template = $task->getEmailTemplate();
            $this->log('info', "new reservations", $template);

            if (!sizeof($template->itineraries)) {
                continue;
            }

            $message = $this->mailer->getMessageByTemplate($template);

            $success = function () use ($task, $conn) {
                foreach ($task->getItineraries() as $it) {
                    $table = EntityItinerary::$table[$it->getData()['Kind']];
                    $id = $it->getData()['ID'];
                    $conn->executeQuery("UPDATE {$table} SET MailDate = NOW() WHERE {$table}ID = {$id}");
                }
            };

            $this->mailer->send($message, [
                Mailer::OPTION_ON_SUCCESSFUL_SEND => $success,
            ]);
        }

        $this->unlock('add', $user->getUserid());
    }

    public function mailChangedReservations(Usr $user, array $options = [])
    {
        if ($this->locked('update', $user->getUserid())) {
            return;
        }

        $this->lock('update', $user->getUserid());

        $where = [
            "t.UserID = " . $user->getUserid(),
            "t.Hidden = 0",
            "(t.AccountID IS NOT NULL OR t.ConfFields IS NOT NULL OR t.Modified = 0)",
            "[StartDate] > (NOW() - INTERVAL " . TRIPS_PAST_DAYS . " DAY)",
        ];

        if (isset($options['filter'])) {
            $where[] = $options['filter'];
        }

        $sources = [];

        $conn = $this->em->getConnection();
        $stmt = $conn->executeQuery(
            $this->tripRep->TripSegmentsSourceSQL($where)
            . "\nUNION\n" . $this->rentalRep->RentalsSourceSQL($where)
            . "\nUNION\n" . $this->reservationRep->ReservationsSourceSQL($where)
            . "\nUNION\n" . $this->restaurantRep->RestaurantsSourceSQL($where)
            //            ."\nUNION\n".$this->parkingRep->ParkingsSourceSQL($where)
        );

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['MailDate']) && !empty($row['MailDate'])) {
                $row['MailDate'] = date_create($row['MailDate']);
            } else {
                $row['MailDate'] = null;
            }
            $sources[$row['SourceID']] = $row;
        }

        if (count($sources)) {
            $keys = "'" . implode("', '", array_keys($sources)) . "'";
            $filter = $this->getSQLWhiteList();

            if (!empty($filter)) {
                $filter = " AND ($filter)";
            }
            $stmt = $conn->executeQuery("
              SELECT
                  MAX(ChangeDate) as ChangeDate,
                  SourceID
              FROM
                  DiffChange
              WHERE
                  SourceID IN (" . $keys . ")
                  $filter
              GROUP BY SourceID
            ");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (isset($row['ChangeDate']) && !empty($row['ChangeDate'])) {
                    $row['ChangeDate'] = date_create($row['ChangeDate']);
                } else {
                    $row['ChangeDate'] = null;
                }
                $sources[$row['SourceID']]['ChangeDate'] = $row['ChangeDate'];
            }

            $ttl = isset($options['ttl']) ? intval($options['ttl']) : 3600;
            $sources = array_filter($sources, function ($v) use ($ttl) {
                if (
                    !isset($v['ChangeDate'])
                    || !$v['ChangeDate']
                    || ($v['ChangeDate'] instanceof \DateTime && (time() - $v['ChangeDate']->getTimestamp()) > $ttl)
                ) {
                    return false;
                }

                if ($v['MailDate'] && $v['MailDate'] >= $v['ChangeDate']) {
                    return false;
                }

                return true;
            });

            $kinds = [];
            $changes = [];

            foreach (array_keys(EntityItinerary::$table) as $kind) {
                $filtered = array_filter($sources, function ($v) use ($kind) {
                    return $v['Kind'] == $kind;
                });
                $kinds[$kind] = $changes[$kind] = [];

                foreach ($filtered as $source) {
                    $kinds[$kind][] = $source['ID'];
                    $changes[$kind][$source['ID']] = $source['ChangeDate'];
                }
            }

            /** @var Task[] $tasks */
            $tasks = [];

            if (count($kinds['T'])) {
                $tasks = array_merge($tasks, $this->getTasks($user, $this->getDetails(
                    $this->tripRep->TripsSQL(array_merge($where, [
                        "t.TripID in (" . implode(',', $kinds['T']) . ")",
                    ])),
                    $changes['T']
                ), self::UPDATE_RESERVATION));
            }

            if (count($kinds['R'])) {
                $tasks = array_merge($tasks, $this->getTasks($user, $this->getDetails(
                    $this->reservationRep->ReservationsSQL(array_merge($where, ["t.ReservationID in (" . implode(',', $kinds['R']) . ")"])),
                    $changes['R']
                ), self::UPDATE_RESERVATION));
            }

            if (count($kinds['E'])) {
                $tasks = array_merge($tasks, $this->getTasks($user, $this->getDetails(
                    $this->restaurantRep->RestaurantsSQL(array_merge($where, ["t.RestaurantID in (" . implode(',', $kinds['E']) . ")"])),
                    $changes['E']
                ), self::UPDATE_RESERVATION));
            }

            if (count($kinds['L'])) {
                $tasks = array_merge($tasks, $this->getTasks($user, $this->getDetails(
                    $this->rentalRep->RentalsSQL(array_merge($where, ["t.RentalID in (" . implode(',', $kinds['L']) . ")"])),
                    $changes['L']
                ), self::UPDATE_RESERVATION));
            }
            //            if (count($kinds['P'])) {
            //                $tasks = array_merge($tasks, $this->getTasks($user, $this->getDetails(
            //                    $this->parkingRep->ParkingsSQL(array_merge($where, ["t.ParkingID in (".implode(',', $kinds['P']).")"])),
            //                    $changes['P']
            //                ), self::UPDATE_RESERVATION));
            //            }
            $this->logger->info(sprintf('tasks count: %d for user %d', count($tasks), $user->getId()));

            foreach ($tasks as $task) {
                $template = $task->getEmailTemplate();
                $this->log('info', "changed reservations", $template);

                if (!sizeof($template->itineraries)) {
                    continue;
                }

                $message = $this->mailer->getMessageByTemplate($template);

                $success = function () use ($task, $conn) {
                    foreach ($task->getItineraries() as $it) {
                        $table = EntityItinerary::$table[$it->getData()['Kind']];
                        $id = $it->getData()['ID'];
                        $conn->exec("UPDATE {$table} SET MailDate = NOW() WHERE {$table}ID = {$id}");
                    }
                };

                $this->mailer->send($message, [
                    Mailer::OPTION_ON_SUCCESSFUL_SEND => $success,
                ]);
            }
        }

        $this->unlock('update', $user->getUserid());
    }

    public function checkMessage($data)
    {
        if ($this->locked($data['mode'], $data['userId'])) {
            $this->delayedProducer->delayedPublish(
                self::DELAY,
                @serialize($data)
            );

            return false;
        }

        return true;
    }

    /**
     * @return MailItinerary[]
     */
    protected function getDetails($sql, array $changeDates = [])
    {
        /** @var MailItinerary[] $result */
        $result = [];
        $connection = $this->em->getConnection();
        $stmt = $connection->executeQuery($sql);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $it = new MailItinerary();
            $it->setData($row);

            if (isset($row['ID'], $changeDates[$row['ID']]) && $changeDates[$row['ID']] instanceof \DateTime) {
                $it->setChangeDate($changeDates[$row['ID']]);
            }
            $result[] = $it;
        }

        return $result;
    }

    /**
     * @param MailItinerary[] $itineraries
     * @param string $mode
     * @return Task[]
     */
    protected function getTasks(Usr $user, array $itineraries, $mode)
    {
        $result = [];
        $modeNew = $mode == self::NEW_RESERVATION;
        $emailToggle = $modeNew ? $user->getEmailnewplans() : $user->getEmailplanschanges();

        foreach ($itineraries as $itinerary) {
            $data = $itinerary->getData();
            $ua = null;

            if (isset($data['UserAgentID'])) {
                $ua = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($data['UserAgentID']);
            }

            // personal
            if (!$user->isBusiness()) {
                // owner - user
                if (!isset($ua)) {
                    if ($emailToggle) {
                        $key = 'u' . $user->getUserid();

                        if (!isset($result[$key])) {
                            $result[$key] = $this->createTask($mode, $user, null, false);
                        }
                        $result[$key]->addItinerary($itinerary);
                    } else {
                        $this->logger->info(sprintf('email toggle is off for user %d', $user->getId()));
                    }
                } else {
                    // owner - family member
                    if (
                        $user->getConnections()->contains($ua) // owner has this family member
                        && $ua->getSendemails() // "Send relevant emails (account expirations and changes) to this person" option
                        && !empty($ua->getEmail())
                    ) {
                        $key = 'f' . $ua->getUseragentid();

                        if (!isset($result[$key])) {
                            $result[$key] = $this->createTask($mode, $user, $ua, false);
                        }
                        $result[$key]->addItinerary($itinerary);
                    }

                    if ($user->isEmailFamilyMemberAlert()) {
                        $key = 'u' . $user->getUserid() . 'f' . $ua->getUseragentid();

                        if (!isset($result[$key])) {
                            $result[$key] = $this->createTask($mode, $user, $ua, true);
                        }
                        $result[$key]->addItinerary($itinerary);
                    }
                }
            } else {
                // business
                /** @var Usr[] $admins */
                //                $admins = $this->context->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessAdmins($user);
                //                if (!isset($ua)) {
                //                    foreach($admins as $admin) {
                //                        $key = 'ad'.$admin->getUserid();
                //                        if (!isset($result[$key])) {
                //                            $_ua = $user->findUserAgent($admin->getUserid());
                //                            $result[$key] = $this->createTask($user, $_ua, $admin->getEmail(), $admin->getFirstname(), $admin->getFullName(), true, true);
                //                        }
                //                        $result[$key]->addItinerary($itinerary);
                //                    }
                //                } else {
                if (isset($ua)) {
                    if (
                        $user->getConnections()->contains($ua) // owner has this family member
                        && $ua->getSendemails() // "Send relevant emails (account expirations and changes) to this person" option
                        && !empty($ua->getEmail())
                    ) {
                        $key = 'f' . $ua->getUseragentid();

                        if (!isset($result[$key])) {
                            $result[$key] = $this->createTask($mode, $user, $ua, false);
                        }
                        $result[$key]->addItinerary($itinerary);
                    }
                }
            }
        }

        return $result;
    }

    protected function createTask($template, Usr $user, ?Useragent $ua = null, $isCopy = false)
    {
        $task = new Task($template, $user, $ua, $isCopy, $this->noForeignFeesCardsQuery);
        $task->setEntityManager($this->em);
        $task->setFormatter($this->formatter);
        $task->setAdManager($this->adManager);
        $task->setScannerApi($this->scannerApi);

        return $task;
    }

    private function locked($mode, $userId)
    {
        $result = (bool) $this->memcached->get($this->lockerKey($mode, $userId));

        if (!$result) {
            $this->logger->warning("Sender: failed to get lock for " . $this->lockerKey($mode, $userId));
        }

        return $result;
    }

    private function lock($mode, $userId)
    {
        // maybe throttler?
        $this->memcached->set($this->lockerKey($mode, $userId), 1, self::LOCK_TTL);
    }

    private function unlock($mode, $userId)
    {
        $this->memcached->delete($this->lockerKey($mode, $userId));
    }

    private function lockerKey($mode, $userId)
    {
        return self::LOCK_PREFIX . '_' . $mode . '_' . $userId;
    }

    private function getSQLWhiteList()
    {
        return "Property IN ('" . it(WhiteList::LIST)
            ->map(function ($property) {
                return addslashes($property);
            })->joinToString("', '") . "')";
    }

    /**
     * @param ReservationNew|ReservationChanged $template
     */
    private function log($level, $message, $template)
    {
        $context = [
            '_aw_itinerary_module' => $template::getEmailKind(),
            'itineraries' => [],
        ];
        $to = $template->getUser();

        if ($to instanceof Usr) {
            $context['UserID'] = $to->getUserid();
            $context['Name'] = $to->getFullName();
            $context['Email'] = $to->getEmail();
        } elseif ($to instanceof Useragent) {
            $context['UserID'] = $to->getAgentid()->getUserid();
            $context['UserAgentID'] = $to->getUseragentid();
            $context['Name'] = $to->getFullName();
            $context['Email'] = $to->getEmail();
        }

        if (isset($template->originalRecipient) && $template->originalRecipient instanceof Useragent) {
            $context['OriginalRecipient'] = $template->originalRecipient->getUseragentid();
        }

        $ids = $numbers = [];

        /** @var MailItinerary $it */
        foreach ($template->itineraries as $it) {
            $id = $it->getData()['Kind'] . $it->getData()['ID'];
            $cIt = &$context['itineraries'][$id];
            $ids[] = $id;
            $numbers[] = $it->getData()['ConfirmationNumber'];
            $cIt['number'] = $it->getData()['ConfirmationNumber'];
            $cIt['segments'] = count($it->getSegments());
            /** @var EntityItinerary $entity */
            $entity = $it->getEntity();
            $cIt['MailDate'] = $entity->getMailDate() ? $entity->getMailDate()->format("Y-m-d H:i:s") : 'null';
        }

        if (sizeof($ids)) {
            $context['Ids'] = implode(", ", $ids);
        }

        if (sizeof($numbers)) {
            $context['Numbers'] = implode(", ", $numbers);
        }

        $this->logger->log($level, $message, $context);
    }
}
