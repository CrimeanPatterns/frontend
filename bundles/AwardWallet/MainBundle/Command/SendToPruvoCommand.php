<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendToPruvoCommand extends Command
{
    private const LAST_ID_PARAM_NAME = 'pruvo_last_reservation_id';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var string
     */
    private $pruvoAuthKey;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        $name = null,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        string $pruvoAuthKey
    ) {
        parent::__construct($name);
        $this->logger = $logger;
        $this->em = $em;
        $this->httpDriver = $httpDriver;
        $this->pruvoAuthKey = $pruvoAuthKey;
    }

    public function configure()
    {
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $reservations = $this->loadReservations();
        $this->sendToPruvo($reservations);
        $this->logger->warning("sent reservations to pruvo", ["count" => count($reservations)]);

        return 0;
    }

    private function loadReservations(): array
    {
        $this->logger->info("loading reservations");
        $lastReservationId = $this->getLastReservationId();
        $q = $this->em->createQuery(
            "select
                r
            from
                AwardWallet\MainBundle\Entity\Reservation r
                join r.geotagid t
            where 
                r.id > :lastReservationId
                and r.travelAgency is not null
                and r.pricingInfo.total > 0
                and r.cancellationDeadline is not null
                " /* Fact of the day: there is city without country: Jerusalem */ . "
                and t.country is not null 
                and t.city is not null
                and r.checkindate > :date
                and r.rooms is not null
                and r.cancellationDeadline > r.checkindate
            order by 
                r.id
            "
        );
        $result = $q->execute(["lastReservationId" => $lastReservationId, "date" => new \DateTime()]);
        $this->logger->info("loaded " . count($result) . " reservations");

        return $result;
    }

    private function getLastReservationId(): int
    {
        $result = (int) $this->em->getConnection()->executeQuery("select Val from Param where Name = ?",
            [self::LAST_ID_PARAM_NAME])->fetchColumn();
        $this->logger->info("loaded last reservation id: $result");

        if ($result === 0) {
            $this->logger->info("first run, will start from 1 week ago");
            $result = (int) $this->em->getConnection()->executeQuery("select ReservationID from Reservation where CreateDate > adddate(now(), -7) order by ReservationID limit 1")->fetchColumn();
            $this->logger->info("set last reservation id to $result");
        }

        return $result;
    }

    private function sendToPruvo(array $reservations)
    {
        $this->logger->info("sending to pruvo, count: " . count($reservations));
        $progress = new ProgressLogger($this->logger, 10, 10);

        foreach ($reservations as $index => $reservation) {
            $this->sendOne($reservation);
            $progress->showProgress("uploading to pruvo", $index);
        }
    }

    private function sendOne(Reservation $reservation)
    {
        if (count($reservation->getRooms()) === 0) {
            $this->output->writeln("skipping {$reservation->getId()} - no rooms");

            return;
        }

        $data = $this->prepareRequestData($reservation);
        $this->output->write("{$reservation->getId()}: {$data['price']} / {$data['tax']} {$data['currencyCode']}, {$data['roomType']}, {$data['rooms']} rooms, {$data['persons']} guests, {$data['arrivalDate']} - {$data['departureDate']}, {$data['address']}, {$data['hotelName']}");

        $response = $this->httpDriver->request(new \HttpDriverRequest(
            'https://www.pruvo.net/api/PushBookings/awardWallet',
            'POST',
            json_encode($data, JSON_PRETTY_PRINT),
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->pruvoAuthKey,
            ]
        ));
        $this->output->writeln(" - " . $response->httpCode);

        if ($response->httpCode < 200 || $response->httpCode >= 300) {
            throw new \Exception("failed to send request, response: {$response->httpCode}: " . substr($response->body, 0, 512));
        }
        $this->em->getConnection()->executeUpdate("insert into Param(Name, Val) values (?, ?) 
            on duplicate key update Val = values(Val)",
            [self::LAST_ID_PARAM_NAME, $reservation->getId()]);
    }

    private function prepareRequestData(Reservation $reservation): array
    {
        $roomTypes = null;

        if (!empty($reservation->getRooms())) {
            $roomTypes = array_map(function (Room $room) {
                return $room->getShortDescription();
            }, $reservation->getRooms());
        }

        $data = [
            "uniqueId" => $reservation->getId(),
            "email" => "dilip.patel.traveldev@gmail.com",
            // "custom" => <the custom data set by us>,
            "price" => $reservation->getPricingInfo()->getTotal(),
            "tax" => $reservation->getPricingInfo()->getTax(),
            "currencyCode" => $reservation->getPricingInfo()->getCurrencyCode(),
            "roomType" => implode(", ", array_unique($roomTypes)),
            "rooms" => $reservation->getRoomCount() ? $reservation->getRoomCount() : 1,
            "persons" => $reservation->getGuestCount() ?? 1,
            "children" => 0,
            "arrivalDate" => $reservation->getCheckindate()->format("m/d/Y"),
            "departureDate" => $reservation->getCheckoutdate()->format("m/d/Y"),
            "address" => $reservation->getAddress(),
            "city" => $reservation->getGeotagid() ? $reservation->getGeotagid()->getCity() : null,
            "country" => $reservation->getGeotagid() ? $reservation->getGeotagid()->getCountry() : null,
            "manageLink" => "https://awardwallet.com/reservation/" . $reservation->getId(),
            "hotelLink" => "https://dummy.url",
            "latLong" => $reservation->getGeotagid() && $reservation->getGeotagid()->getLat() !== null ? $reservation->getGeotagid()->getLat() . "," . $reservation->getGeotagid()->getLng() : null,
            "hotelName" => $reservation->getHotelname(),
            "mealPlan" => "",
            "tripId" => $reservation->getId(),
            "lastFreeCancelTime" => $reservation->getCancellationDeadline()->format("m/d/Y"),
        ];

        return $data;
    }
}
