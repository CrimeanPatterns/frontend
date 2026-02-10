<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TripSegmentMigrationCommand extends Command
{
    protected static $defaultName = 'aw:timeline:migrate-itineraries-dates';

    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    private bool $dryRun;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Migrating itineraries that have an end date earlier than a start date')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry-run, do not do anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dryRun = (bool) $input->getOption('dry-run');
        $this->logger->info(sprintf('dry run: %s', $this->dryRun ? 'true' : 'false'));

        $this->processTrips();
        $this->processRentals();
        $this->processRestaurants();
        $this->processReservations();
        $this->processParkings();

        $output->writeln('done.');

        return 0;
    }

    private function processTrips()
    {
        $sql = "
            SELECT
                TripSegmentID
            FROM
                TripSegment
            WHERE
                TIMESTAMPDIFF(SECOND, DepDate, ArrDate) < 86400
        ";
        $rowsCount = $this->em->getConnection()->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $this->logger->info(sprintf('start processing trips, rows: %d', $rowsCount));

        $processed = 0;
        $modified = 0;
        $tsRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
        $query = $this->em->getConnection()->executeQuery($sql);

        while ($tsId = $query->fetch(\PDO::FETCH_COLUMN)) {
            /** @var Tripsegment $ts */
            $ts = $tsRep->find($tsId);

            if (!$ts) {
                continue;
            }

            $depDate = $this->correctTimezone(clone $ts->getDepartureDate(), $ts->getDepgeotagid());
            $arrDate = $this->correctTimezone(clone $ts->getArrivalDate(), $ts->getArrgeotagid());
            $duration = $ts->getDuration();

            if ($depDate->getTimestamp() > $arrDate->getTimestamp()) {
                $newArrDate = $this->getNewArrDate($depDate, $arrDate, $duration);
                $this->logger->info(sprintf(
                    'processing ts #%d, dep: %s, arr: %s, duration: %s, new arrDate: %s',
                    $tsId,
                    $this->getDateTimeInfo($depDate),
                    $this->getDateTimeInfo($arrDate),
                    $duration ?? 'none',
                    $this->getDateTimeInfo($newArrDate)
                ));

                if (!$this->dryRun) {
                    $ts->setArrivalDate(new \DateTime($newArrDate->format('Y-m-d H:i:s')));
                    $this->em->flush();
                    $modified++;
                }
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('%d processed trips, %d modified', $processed, $modified));
    }

    private function getNewArrDate(\DateTime $depDate, \DateTime $arrDate, ?string $duration): \DateTime
    {
        $default = (clone $depDate)
            ->modify('+3 hours')
            ->setTimezone($arrDate->getTimezone());

        if ($duration) {
            $duration = trim($duration);
            $newDate = (clone $depDate)
                ->setTimezone($arrDate->getTimezone());

            if (preg_match("/^((\d+)\s*(?:hr?s?|hours?)\s*)?((\d+)\s*(?:mn?|mins?|minutes?))?$/i", $duration, $matches)) {
                if (isset($matches[2]) || isset($matches[4])) {
                    if (isset($matches[2])) {
                        $newDate->modify(sprintf('+%d hour', $matches[2]));
                    }

                    if (isset($matches[4])) {
                        $newDate->modify(sprintf('+%d minute', $matches[4]));
                    }

                    return $newDate;
                }
            } elseif (preg_match("/^(\d+)\:(\d+)(h|m)?$/i", $duration, $matches)) {
                return $newDate->modify(sprintf('+%d hour +%d minute', $matches[1], $matches[2]));
            }
        }

        return $default;
    }

    private function processRentals()
    {
        $sql = "
            SELECT
                RentalID
            FROM
                Rental
            WHERE
                TIMESTAMPDIFF(SECOND, PickupDatetime, DropoffDatetime) < 86400
        ";
        $rowsCount = $this->em->getConnection()->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $this->logger->info(sprintf('start processing rentals, rows: %d', $rowsCount));

        $processed = 0;
        $modified = 0;
        $rentalRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Rental::class);
        $query = $this->em->getConnection()->executeQuery($sql);

        while ($rentalId = $query->fetch(\PDO::FETCH_COLUMN)) {
            /** @var Rental $rental */
            $rental = $rentalRep->find($rentalId);

            if (!$rental) {
                continue;
            }

            $startDate = $this->correctTimezone(clone $rental->getPickupdatetime(), $rental->getPickupgeotagid());
            $endDate = $this->correctTimezone(clone $rental->getDropoffdatetime(), $rental->getDropoffgeotagid());

            if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
                $this->logger->info(sprintf(
                    'processing rental #%d, start: %s, end: %s',
                    $rentalId,
                    $this->getDateTimeInfo($startDate),
                    $this->getDateTimeInfo($endDate)
                ));

                if (!$this->dryRun) {
                    $rental->setDropoffdatetime(
                        new \DateTime($this->getNewEndDate($startDate, $endDate, '+85 hours')
                            ->format('Y-m-d H:i:s'))
                    );
                    $this->em->flush();
                    $modified++;
                }
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('%d processed rentals, %d modified', $processed, $modified));
    }

    private function processRestaurants()
    {
        $sql = "
            SELECT
                RestaurantID
            FROM
                Restaurant
            WHERE
                TIMESTAMPDIFF(SECOND, StartDate, EndDate) < 86400
        ";
        $rowsCount = $this->em->getConnection()->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $this->logger->info(sprintf('start processing restaurants, rows: %d', $rowsCount));

        $processed = 0;
        $modified = 0;
        $restaurantRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class);
        $query = $this->em->getConnection()->executeQuery($sql);

        while ($restaurantId = $query->fetch(\PDO::FETCH_COLUMN)) {
            /** @var Restaurant $restaurant */
            $restaurant = $restaurantRep->find($restaurantId);

            if (!$restaurant) {
                continue;
            }

            $startDate = $this->correctTimezone(clone $restaurant->getStartdate(), $restaurant->getGeotagid());
            $endDate = $this->correctTimezone(clone $restaurant->getEnddate(), $restaurant->getGeotagid());

            if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
                $this->logger->info(sprintf(
                    'processing restaurant #%d, start: %s, end: %s',
                    $restaurantId,
                    $this->getDateTimeInfo($startDate),
                    $this->getDateTimeInfo($endDate)
                ));

                if (!$this->dryRun) {
                    $restaurant->setEnddate(
                        new \DateTime($this->getNewEndDate($startDate, $endDate, '+76 hours')
                            ->format('Y-m-d H:i:s'))
                    );
                    $this->em->flush();
                    $modified++;
                }
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('%d processed restaurants, %d modified', $processed, $modified));
    }

    private function processReservations()
    {
        $sql = "
            SELECT
                ReservationID
            FROM
                Reservation
            WHERE
                TIMESTAMPDIFF(SECOND, CheckInDate, CheckOutDate) < 86400
        ";
        $rowsCount = $this->em->getConnection()->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $this->logger->info(sprintf('start processing reservations, rows: %d', $rowsCount));

        $processed = 0;
        $modified = 0;
        $reservationRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class);
        $query = $this->em->getConnection()->executeQuery($sql);

        while ($reservationId = $query->fetch(\PDO::FETCH_COLUMN)) {
            /** @var Reservation $reservation */
            $reservation = $reservationRep->find($reservationId);

            if (!$reservation) {
                continue;
            }

            $startDate = $this->correctTimezone(clone $reservation->getCheckindate(), $reservation->getGeotagid());
            $endDate = $this->correctTimezone(clone $reservation->getCheckoutdate(), $reservation->getGeotagid());

            if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
                $this->logger->info(sprintf(
                    'processing reservation #%d, start: %s, end: %s',
                    $reservationId,
                    $this->getDateTimeInfo($startDate),
                    $this->getDateTimeInfo($endDate)
                ));

                if (!$this->dryRun) {
                    $reservation->setCheckoutdate(
                        new \DateTime($this->getNewEndDate($startDate, $endDate, '+62 hours')
                            ->format('Y-m-d H:i:s'))
                    );
                    $this->em->flush();
                    $modified++;
                }
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('%d processed reservations, %d modified', $processed, $modified));
    }

    private function processParkings()
    {
        $sql = "
            SELECT
                ParkingID
            FROM
                Parking
            WHERE
                TIMESTAMPDIFF(SECOND, StartDatetime, EndDatetime) < 86400
        ";
        $rowsCount = $this->em->getConnection()->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $this->logger->info(sprintf('start processing parkings, rows: %d', $rowsCount));

        $processed = 0;
        $modified = 0;
        $parkingRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Parking::class);
        $query = $this->em->getConnection()->executeQuery($sql);

        while ($parkingId = $query->fetch(\PDO::FETCH_COLUMN)) {
            /** @var Parking $parking */
            $parking = $parkingRep->find($parkingId);

            if (!$parking) {
                continue;
            }

            $startDate = $this->correctTimezone(clone $parking->getStartDatetime(), $parking->getGeoTagID());
            $endDate = $this->correctTimezone(clone $parking->getEndDatetime(), $parking->getGeoTagID());

            if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
                $this->logger->info(sprintf(
                    'processing parking #%d, start: %s, end: %s',
                    $parkingId,
                    $this->getDateTimeInfo($startDate),
                    $this->getDateTimeInfo($endDate)
                ));

                if (!$this->dryRun) {
                    $parking->setEndDatetime(
                        new \DateTime($this->getNewEndDate($startDate, $endDate, '+7 day')
                            ->format('Y-m-d H:i:s'))
                    );
                    $this->em->flush();
                    $modified++;
                }
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('%d processed parkings, %d modified', $processed, $modified));
    }

    private function getNewEndDate(\DateTime $startDate, \DateTime $endDate, string $modify): \DateTime
    {
        return (clone $startDate)
            ->modify($modify)
            ->setTimezone($endDate->getTimezone());
    }

    private function getDateTimeInfo(\DateTime $dateTime): string
    {
        return sprintf(
            '%s (%s, %s)',
            $dateTime->format('Y-m-d H:i:s'),
            $dateTime->getTimezone()->getName(),
            $dateTime->getOffset() / 3600
        );
    }

    private function correctTimezone(?\DateTime $dateTime, ?Geotag $geotag): ?\DateTime
    {
        return Geotag::getLocalDateTimeByGeoTag($dateTime, $geotag);
    }
}
