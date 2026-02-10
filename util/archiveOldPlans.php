<?
require __DIR__."/../web/kernel/public.php";
require_once __DIR__."/../web/trips/common.php";
require_once __DIR__."/../web/kernel/TSchemaManager.php";

class PlansArchiver{

	/**
	 * @var TSchemaManager
	 */
	protected $manager;
	protected $usedTables = [];
	protected $deletedRows = 0;
	protected $allowedTables = ["Restaurant", "Reservation", "Trip", "TripSegment", "Rental"];
	protected $dontCopyTables = ["TravelPlanShare", "Direction"];
	protected $pastDate;
	protected $sortOrder;
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $archiveConnection;
    /**
     * @var \Doctrine\DBAL\Connection
     */
	private $replicaConn;
    /**
     * @var \Doctrine\DBAL\Connection
     */
	private $connection;

	public function __construct(){
		$this->manager = new TSchemaManager();
		$this->manager->ReturnFields = true;
		$this->archiveConnection = getSymfonyContainer()->get("doctrine.dbal.unbuffered_archive_connection");
		$this->replicaConn = getSymfonyContainer()->get("doctrine.dbal.read_replica_unbuffered_connection");
		$this->pastDate = "'" . date("Y-m-d H:i:s", time() - SECONDS_PER_DAY * TRIPS_DELETE_DAYS) . "'";
		$sortOrder = $this->allowedTables;
		array_unshift($sortOrder, "GeoTag");
		$this->sortOrder = array_flip($sortOrder);
		$this->connection = getSymfonyContainer()->get("database_connection");
	}

	public function archive(){
		echo "archiving data older than {$this->pastDate}\n";
		$this->deleteIts();
		echo "done, deleted rows: {$this->deletedRows}\n";
	}

	protected function deleteIts(){
		foreach(\AwardWallet\MainBundle\Entity\Itinerary::$table as $kind => $table){
			echo "deleting {$table}s\n";
			switch($table){
                case "Trip":
                    $fields = array_map(function($field){ return "t.$field"; }, array_keys($this->manager->Tables["Trip"]["Fields"]));
                    $sql = "select " . implode(", ", $fields) . ", 
                        min(ts.DepDate) as MinDate, max(ts.DepDate) as MaxDate
                        from Trip t
                        join TripSegment ts on t.TripID = ts.TripID
                        where ts.DepDate < {$this->pastDate}
                        group by " . implode(", ", $fields) . "
                        having max(ts.DepDate) < {$this->pastDate}";
                    break;
                case "Reservation":
                    $sql = "select * from Reservation where CheckoutDate < {$this->pastDate}";
                    break;
                case "Rental":
                    $sql = "select * from Rental where DropoffDateTime < {$this->pastDate}";
                    break;
                case "Restaurant":
                    $sql = "select * from Restaurant where EndDate < {$this->pastDate}";
                    break;
                default:
                    throw new \Exception("unknown table: $table");
            }

			echo $sql . "\n";
			$q = $this->replicaConn->executeQuery($sql);
			$rows = [];
			while($row = $q->fetch(\PDO::FETCH_ASSOC)){
			    $rows[] = $row;
				if(count($rows) >= 100) {
                    $this->delete($table, $kind, $rows);
                    $rows = [];
                }
			}
			if(!empty($rows))
                $this->delete($table, $kind, $rows);
		}
	}

	protected function delete($table, $kind, array $rows){
		set_time_limit(120);
		$geoTagsIds = [];

		switch($table){
            case "Trip":
                $rows = array_map(function(array $row){ unset($row['MinDate']); unset($row['MaxDate']); return $row; }, $rows);
                $segments = $this->loadRows("TripSegment", "TripID in (" . implode(",", array_map(function(array $row){ return $row['TripID']; }, $rows)) . ")");
                $geoTagsIds = array_merge($geoTagsIds, $this->extractGeoTags($segments, ["DepGeoTagID", "ArrGeoTagID"]));
                break;
            case "Reservation":
                $geoTagsIds = array_merge($geoTagsIds, $this->extractGeoTags($rows, ["GeoTagID"]));
                break;
            case "Rental":
                $geoTagsIds = array_merge($geoTagsIds, $this->extractGeoTags($rows, ["PickupGeoTagID", "DropoffGeoTagID"]));
                break;
            case "Restaurant":
                $geoTagsIds = array_merge($geoTagsIds, $this->extractGeoTags($rows, ["GeoTagID"]));
                break;
        }

        if(!empty($geoTagsIds))
            $this->copyRows("GeoTag", $this->loadRows("GeoTag", "GeoTagID in (".implode(",", $geoTagsIds).")"));

		$this->copyRows($table, $rows);

		$this->deleteFromTable($table, $rows);
	}

	private function loadRows($table, $condition)
    {
        return $this->connection->executeQuery("select * from $table where $condition")->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function extractGeoTags(array $rows, array $fields)
    {
        $tagIds = array_reduce($rows, function(array $sum, array $row) use($fields){
            foreach ($fields as $field)
                if(!empty($row[$field]))
                    $sum[] = $row[$field];
            return $sum;
        }, []);
        return $tagIds;
    }

    private function deleteFromTable($table, array $rows)
    {
        if(empty($rows))
            return;
        $deleted = $this->connection->executeUpdate("delete from $table where {$table}ID in (".implode(",", $this->getIds($rows)).")");
        echo "deleted {$deleted} rows from $table\n";
        $this->deletedRows += $deleted;
    }

    private function getIds(array $rows){
	    return array_map(function(array $row){ $first = array_shift($row); return $first; }, $rows);
    }

	protected function copyRows($table, array $rows){
	    if(empty($rows))
	        return;
	    $packet = "";
	    echo "copy " . count($rows) . " rows from $table\n";
	    $fields = $this->manager->Tables[$table]['Fields'];
		foreach($rows as $row){
			foreach($row as $key => &$value){
				if($value == '' && $fields[$key]['Null'])
					$value = "null";
				else
					$value = "'".addslashes($value)."'";
			}
			$packet .= InsertSQL($table, $row, false, true) . "; ";
			if(strlen($packet) > 8000) {
                $this->archiveConnection->executeUpdate($packet);
                $packet = "";
            }
		}
		if($packet !== "")
            $this->archiveConnection->executeUpdate($packet);
	}

}

$archiver = new PlansArchiver();
$archiver->archive();

