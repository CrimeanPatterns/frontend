<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FutureHotelBookingsCommand extends Command
{
    public static $defaultName = 'aw:future-hotel-bookings';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Query dump of all future hotel bookings')
            ->addOption('output-file', 'f', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $q = $this->connection->executeQuery("
                SELECT
                    r.ReservationID AS ID,
                    r.HotelName,
                    COALESCE(g.Address, r.Address) AS HotelFullAddress,
                    g.City AS HotelCity,
                    r.ReservationDate AS BookingDate,
                    r.Cost AS TotalCost,
                    r.CheckInDate,
                    r.CheckOutDate,
                    r.CancellationDeadline,
                    r.RoomCount AS NumberOfRooms,
                    r.Rooms
                FROM
                    Reservation r
                    LEFT JOIN GeoTag g ON r.GeoTagID = g.GeoTagID
                    LEFT JOIN Account a ON r.AccountID = a.AccountID
                WHERE
                    COALESCE(r.ProviderID, a.ProviderID) IN (
                        22, /* Hilton (Honors) */
                        427, /* Hilton Grand Vacations (Club) */
                        17, /* Marriott Bonvoy */
                        1540, /* Marriott Vacation Club */
                        10, /* Hyatt (World of Hyatt) */
                        517, /* Hyatt (Vacation Club) */
                        12, /* IHG Hotels & Resorts (One Rewards) */
                        1075 /* Four Seasons */
                    )
                    AND CheckInDate > NOW() + INTERVAL 1 DAY /* future */
                ORDER BY r.CheckInDate
            ");

            $outputFile = fopen($input->getOption('output-file'), 'wb');
            fputcsv($outputFile, [
                'ID',
                'Hotel Name',
                'Hotel Full Address',
                'Hotel City',
                'Booking Date',
                'Total Cost',
                'Check In Date',
                'Check Out Date',
                'Cancellation Deadline',
                'Number of Rooms',
                'Room Type',
                'Description',
                'Rate',
                'Rate Type',
            ]);

            while ($row = $q->fetchAssociative()) {
                $rooms = @unserialize($row['Rooms']);
                unset($row['Rooms']);
                $fields = array_values($row);

                if (is_array($rooms)) {
                    $prepared = [
                        [],
                        [],
                        [],
                        [],
                    ];

                    foreach ($rooms as $room) {
                        if ($room instanceof Room) {
                            $prepared[0][] = $room->getShortDescription();
                            $prepared[1][] = $room->getLongDescription();
                            $prepared[2][] = $room->getRate();
                            $prepared[3][] = $room->getRateDescription();
                        }
                    }

                    $fields = array_merge($fields, $this->prepareRooms($prepared));
                } else {
                    $fields = array_merge($fields, ['', '', '', '']);
                }

                fputcsv($outputFile, $fields);
            }
        } finally {
            fclose($outputFile);
        }

        $output->writeln('done');

        return 0;
    }

    private function prepareRooms(array $columns): array
    {
        $prepared = [];

        foreach ($columns as $column) {
            if (count(array_unique($column)) === 1) {
                $column = array_unique($column);
            }

            $column = array_values($column);
            $count = count($column);

            foreach ($column as $k => $item) {
                $preparedItem = StringHandler::isEmpty($item) ? '-' : $item;

                if ($count === 1) {
                    $column[$k] = $preparedItem;
                } else {
                    $column[$k] = sprintf('%d. %s', $k + 1, $preparedItem);
                }
            }

            $prepared[] = implode("\n\n", $column);
        }

        return $prepared;
    }
}
