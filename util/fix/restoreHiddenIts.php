<?php
require __DIR__ . '/../../app/setUp.php';

$container = getSymfonyContainer();

$opts = getopt("s:p:fu:t:");

echo "opening query..\n";
$unbufConn = $container->get("doctrine.dbal.unbuffered_connection");
$connection = $container->get("doctrine.dbal.default_connection");

$table = $opts['t'];

switch ($opts['t']) {
    case "TripSegment":
        $sql = "select 
            TripSegmentID as ID, concat(DepDate, ' --- ', DepCode, ' -> ', ArrCode) as Name 
        from 
            TripSegment 
            join Trip on Trip.TripID = TripSegment.TripID
        where
            DepDate > adddate(now(), -14)
            " . ( isset($opts['u']) ? " and Trip.UserID = " . (int)$opts['u'] : "" ) . " 
            and TripSegment.Hidden = 1
            and Trip.Copied = 0
            and Trip.Moved = 0";
        break;
    case "Rental":
        $sql = "select 
            RentalID as ID, PickupDatetime as Name 
        from 
            Rental
        where
            DropoffDatetime > adddate(now(), -30)
            " . ( isset($opts['u']) ? " and Rental.UserID = " . (int)$opts['u'] : "" ) . " 
            and Rental.Hidden = 1
            and Rental.Moved = 0";
        break;
    case "Reservation":
        $sql = "select 
            ReservationID as ID, concat(CheckinDate, ' ', HotelName)  as Name 
        from 
            Reservation
        where
            CheckOutDate > adddate(now(), -14)
            " . ( isset($opts['u']) ? " and Reservation.UserID = " . (int)$opts['u'] : "" ) . " 
            and Reservation.Hidden = 1
            and Reservation.Moved = 0";
        break;
    case "Restaurant":
        $sql = "select 
            RestaurantID as ID, concat(StartDate, ' ', Name)  as Name 
        from 
            Restaurant
        where
            EndDate > adddate(now(), -14)
            " . ( isset($opts['u']) ? " and Restaurant.UserID = " . (int)$opts['u'] : "" ) . " 
            and Restaurant.Hidden = 1
            and Restaurant.Moved = 0";
        break;
    case "Trip":
        $sql = "select 
            Trip.TripID as ID, concat(RecordLocator, ' ', DepDate, ' --- ', DepCode, ' -> ', ArrCode) as Name 
        from 
            TripSegment 
            join Trip on Trip.TripID = TripSegment.TripID
        where
            DepDate > adddate(now(), -14)
            and Cancelled = 0
            " . ( isset($opts['u']) ? " and Trip.UserID = " . (int)$opts['u'] : "" ) . " 
            and Trip.Hidden = 1
            and Trip.Moved = 0";
        break;
    default:
        die("unknown table\n");
}

$q = $unbufConn->executeQuery($sql);

$sourceConnection = getSourceConnection($opts['s'], $opts['p']);
$sourceQuery = $sourceConnection->prepare("select Hidden from $table where {$table}ID = ? and Hidden = 0");
$fixQuery = $connection->prepare("update {$table} set Hidden = 0 where {$table}ID = ? limit 1");

$fix = isset($opts['f']);

echo "scanning\n";

$fixed = 0;
$scanned = 0;
while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
    $scanned++;
    if (($scanned % 1000) === 0) {
        echo "processed $scanned, fixed $fixed\n";
    }
    $sourceQuery->execute([$row['ID']]);
    $sourceRow = $sourceQuery->fetchColumn();
    if ($sourceRow !== false) {
        echo ( $fix ? "fixing" : "found" ) . " {$row['ID']}: {$row['Name']}\n";
        $fixed++;
        if ($fix) {
            $fixQuery->execute([$row['ID']]);
        }
    }
}

echo "done, scanned: $scanned, fixed: $fixed\n";

function getSourceConnection($host, $port) : \PDO
{
    $connection = new \PDO(
        "mysql:host=" . $host .  ';dbname=awardwallet;port=' . $port,
        "awardwallet",
        "awardwallet",
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_PERSISTENT => false,
        ]
    );
    $connection->exec("
        SET wait_timeout=86400;
        SET interactive_timeout=86400;
    ");
    return $connection;
}

