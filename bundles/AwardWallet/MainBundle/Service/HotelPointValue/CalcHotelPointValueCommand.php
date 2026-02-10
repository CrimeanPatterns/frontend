<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CalcHotelPointValueCommand extends Command
{
    public static $defaultName = 'aw:calc-hotel-point-value';

    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private PointValueCalculator $calculator;
    private BrandMatcher $brandMatcher;
    private OutputInterface $output;
    private PriceFinder $priceFinder;
    private SpentAwardsFilter $spentAwardsFilter;
    private InputInterface $input;
    private HotelFinder $hotelFinder;

    private Connection $connection;
    private ReservationRepository $reservationRepo;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        PointValueCalculator $calculator,
        BrandMatcher $brandMatcher,
        PriceFinder $priceFinder,
        SpentAwardsFilter $spentAwardsFilter,
        HotelFinder $hotelFinder
    ) {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->calculator = $calculator;
        $this->brandMatcher = $brandMatcher;
        $this->priceFinder = $priceFinder;
        $this->spentAwardsFilter = $spentAwardsFilter;
        $this->hotelFinder = $hotelFinder;
        $this->connection = $em->getConnection();
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply changes, otherwise just search')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'dql where')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'max records')
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'searchPrices | checkAltHotels',
                'searchPrices');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $where = [];

        if ($input->getOption('where')) {
            $where[] = $input->getOption('where');
        }

        if ($input->getOption('operation') === 'checkAltHotels') {
            $where[] = "hpv.HotelPointValueID is not null";
            $where[] = "hpv.Status not in ('" . implode("', '", CalcMileValueCommand::EXCLUDED_STATUSES) . "')";
        }

        if (count($where) === 0) {
            $output->writeln("no where specified");

            return false;
        }

        $this->output = $output;
        $this->input = $input;
        $this->reservationRepo = $this->em->getRepository(Reservation::class);

        $reservations = $this->loadReservations(implode(" and ", $where), $input->getOption('limit'));
        call_user_func([$this, $input->getOption('operation')], $reservations);
        $this->logger->info("done, processed " . count($reservations) . " reservations");
    }

    private function loadReservations(string $where, ?int $limit): array
    {
        $this->output->writeln("loading reservations: $where");

        $results = $this->em->getConnection()->executeQuery("
            select
                r.ReservationID
            from
                Reservation r
                left join HotelPointValue hpv on r.ReservationID = hpv.ReservationID
            where 
                r.ProviderID is not null 
                and r.SpentAwards is not null 
                and $where
        " . ($limit ? " limit $limit" : ""))->fetchFirstColumn();

        $results = array_map(fn ($id) => $this->em->find(Reservation::class, $id), $results);

        $this->output->writeln("loaded " . count($results) . " rows");

        return $results;
    }

    /**
     * @param Reservation[] $reservations
     */
    private function searchPrices(array $reservations): void
    {
        ConsoleTable::render($reservations, $this->output);

        if ($this->input->getOption('apply')) {
            foreach ($reservations as $reservation) {
                $this->calculator->updateItinerary($reservation, false);
            }
            ConsoleTable::render($reservations, $this->output);
        }
    }

    private function checkAltHotels(array $reservations): void
    {
        $results = it($reservations)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) {
                $this->output->writeln("processed $ticksCounter records..");
            })
            ->map(fn ($reservationId) => $this->reservationRepo->find($reservationId))
            ->map(fn ($reservation) => $this->checkAltHotelAddress($reservation))
            ->onNth(100, function () {
                $this->em->clear();
            })
            ->filter(fn (?array $mismatch) => $mismatch !== null)
            ->toArray();

        if (count($results) > 0) {
            $this->output->writeln("found " . count($results) . " mismatches");
            $table = new Table($this->output);
            $table->setRows($results);
            $table->setHeaders(array_keys($results[0]));
            $table->render();
        } else {
            $this->output->writeln("no mismatches found");
        }

        if (count($results) > 0 && $this->input->getOption('apply')) {
            $this->output->writeln("applying fixes");
            it($results)
                ->apply(function (array $match) {
                    $this->connection->executeStatement(
                        "update HotelPointValue 
                        set Status = :status, Note = :note
                        where HotelPointValueID = :id",
                        [
                            "status" => CalcMileValueCommand::STATUS_ERROR,
                            "note" => "alt hotel mismatch",
                            "id" => $match['ID'],
                        ]
                    );
                });
        }
    }

    private function checkAltHotelAddress(Reservation $reservation): ?array
    {
        $spentAwards = $this->spentAwardsFilter->filter($reservation->getPricingInfo()->getSpentAwards());

        if ($spentAwards === null) {
            $this->output->writeln("could not filter spentAwards for reservation {$reservation->getId()}: {$reservation->getPricingInfo()->getSpentAwards()}");

            return null;
        }

        $brand = $this->brandMatcher->match($reservation->getHotelname(), $reservation->getProvider()->getId());
        $params = new PointValueParams($reservation, $spentAwards, $brand);
        $params->setCheckinDate(new \DateTime("+30 day"));
        $params->setCheckoutDate(new \DateTime("+31 day"));
        $params->setGuestCount(1);

        $hotel = $this->hotelFinder->searchHotel(
            $params->getHotelname(),
            $params->getLat(),
            $params->getLng(),
            $params->getBrand()
        );

        if ($hotel === null) {
            $this->output->writeln("hotel {$params->getHotelName()} not found");

            return null;
        }

        //        $price = $this->priceFinder->searchByHotelId($params, $hotel->getId());

        if ($hotel === null || $hotel->getName() !== $reservation->getHotelPointValue()->getAlternativeHotelName()) {
            $result = [
                'ID' => $reservation->getHotelPointValue()->getId(),
                'Reservation Hotel Name' => $reservation->getHotelname(),
                //                'Reservation Address' => $reservation->getGeotagid()->getAddressline(),
                'Old Alt Hotel Name' => $reservation->getHotelPointValue()->getAlternativeHotelName(),
                'New Alt Hotel Name' => $hotel ? $hotel->getName() : '',
                //                'New Alt Hotel Address' => $price ? $price->getAddress() : '',
            ];

            $this->output->writeln(ImplodeAssoc(": ", ", ", $result));

            return $result;
        }

        return null;
    }
}
