<?

use GeoIp2\Exception\AddressNotFoundException;

abstract class OfferPlugin{

    const BATCH_LIMIT = 500;
    
    protected $offerId;
	/**
	 * @var \Doctrine\Persistence\ManagerRegistry
	 */
	protected $doctrine;
	protected $lastProgressTime;
	private   $lastUserId = 0;
	private   $partialSearch = false;
	public    $logging = true;

    public function __construct($offerId, $doctrine = null){
    	$this->offerId = $offerId;
		if(isset($doctrine))
			$this->doctrine = $doctrine;
		else
			$this->doctrine = getSymfonyContainer()->get('doctrine');
		if (php_sapi_name() == 'cli')
			$this->lastUserId = intval($this->doctrine->getConnection()->executeQuery("select LastUserID from Offer where OfferID = ?", [$this->offerId])->fetchColumn(0));
    }
    
    protected function addUser($userId, array $params){
        $this->addUsers([[$userId, $params]]);
    }

    protected function addUsers(array $usersData)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->doctrine->getConnection();

        foreach (array_chunk($usersData, self::BATCH_LIMIT) as $usersDataChunk) {
            // build sql values for insert
            $values = implode(', ' , array_map(
                function (array $userData) {
                    list($userId, $params) = $userData;
                    $userId = (int) $userId;
                    $params = (array) $params;
                    $p = addslashes(implode("\n",
                        /* zip */array_map(function ($k, $v) { return $k.'='.$v; }, array_keys($params), array_values($params))
                    ));

                    return "({$this->offerId}, {$userId}, now(), 0, '{$p}')";
                },
                $usersDataChunk
            ));

			try {
				$connection->executeQuery("insert into OfferUser (OfferID, UserID, CreationDate, Manual, Params) values {$values} on duplicate key update CreationDate = now(), Params = VALUES(Params)");
			}
			catch(\Doctrine\DBAL\DBALException $e){
				// ignore deleted users
				if(stripos($e->getMessage(), "OfferUser_ibfk_1") === false)
					throw $e;
			}
        }
    }

    public function checkUser($userId, $offerUserId){
        return true;
    }
    
    public function afterShow($userId, $offerUserId, array $params){
    }

    public function getDescription(){
    	return '';
    }

	public function getParams($offerUserId, $preview = false, $params = null) {
		$result = array();
		if (!$preview || !isset($params)){
			$sql = $this->doctrine->getConnection()->executeQuery("
						SELECT Params FROM OfferUser
						WHERE OfferUserID = ?",
					array($offerUserId),
					array(\PDO::PARAM_INT));
			$q = $sql->fetchAll();
			$lines = explode("\n", $q['0']['Params']);
		}
		else
			$lines = explode("\n", $params);
		foreach ($lines as $row){
			if (strpos($row, '=') != false){
				$param = explode('=', $row)[0];
				$value = explode('=', $row, 2)[1];
				$result[$param] = str_replace("\r", "", $value);
			}
		}
		return $result;
	}
    
    public function render($offerUserId, $params){
	// if $params is set, render works like a preview ignoring $offerUserId
    }
    
    public function updateOffer(){
		while(ob_get_level() > 0)
			ob_end_flush();
        $q = new TQuery("SELECT now() as `time`");
        $startDate = strtotime($q->Fields['time']);
		$maxUserId = $this->doctrine->getConnection()->executeQuery("select Max(UserID) from Usr")->fetchColumn(0);
        $i = $this->searchUsers();
        $this->log("$i users have been added");
        if (php_sapi_name() != 'cli'){
            $mlink = "<a href = \"http://".$_SERVER['SERVER_NAME']."/manager/list.php?Schema=";
            echo $mlink."OfferUser&OfferID={$this->offerId}\">Go to OfferUser table</a><br />";
            echo $mlink."Offer\">Back to Offer table</a>";
        }
		if(!$this->partialSearch)
        	$this->deleteOldUsers($startDate);
		$this->log("search complete, last user recorded: " . $maxUserId);
		$this->doctrine->getConnection()->executeUpdate("update Offer set LastUserID = ? where OfferID = ?", [$maxUserId, $this->offerId]);
    }
    
    protected abstract function searchUsers();
    
    protected function deleteOldUsers($startDate){
        global $Connection;
        $Connection->Execute("DELETE FROM OfferUser WHERE OfferID = $this->offerId AND CreationDate < '".date("Y-m-d H:i:s", $startDate)."' AND Manual = 0 AND Agreed IS NULL");
		$this->log("deleted records: " . $Connection->GetAffectedRows());
	}

	protected function showProgress($position){
		if(!isset($this->lastProgressTime) || (time() - $this->lastProgressTime) > 10){
			$this->lastProgressTime = time();
			$this->log("processed {$position} rows..");
		}
	}

	protected function log($s){
		if(!$this->logging)
			return;

        echo date("Y-m-d H:i:s ").$s."\n";
        if (php_sapi_name() != 'cli')
            echo "<br />";
	}

	protected function getLastUserId(){
		if($this->lastUserId > 0)
			$this->partialSearch = true;
		$this->log("searching from userId: " . $this->lastUserId);
		return $this->lastUserId;
	}

    protected function addAllUsers($filter = ''){
   		$u = 0;
   		$this->log('Setting time limit to 59 seconds...');
   		flush();
   		set_time_limit(59);
   		$this->log(sprintf('Searching for all users%s...', $filter ? " with filter: '{$filter}' " : ''));
   		flush();
   		$q = new TQuery("SELECT UserID FROM Usr WHERE 1=1 AND UserID > " . $this->getLastUserId() . ($filter ? ' AND '.$filter : ''));
   		set_time_limit(59);
   		$this->log('Adding users...');
   		flush();

        $batch = [];
   		foreach ($q as $r){
            $batch[] = [$r['UserID'], []];
   			$u++;
   			if ($u % 1000 == 0){
   				set_time_limit(59);
                $this->addUsers($batch);
   				$this->log($u.' users so far...');
   				flush();
                $batch = [];
   			}
   		}

        if ($batch) {
            $this->addUsers($batch);
            $this->log(($u).' users so far...');
            flush();
        }

   		return $u;
   	}

    protected function getCountryByIpResolver() : \Closure
    {
        $countryDb = getSymfonyContainer()->get('aw.geoip.country');

        return function ($ip) use ($countryDb) : ?string {
            try {
                if (
                    ($record = $countryDb->country($ip)) &&
                    ($country = $record->country)
                ) {
                    return \strtoupper($country->isoCode);
                }
            } catch (\Throwable $e) {}

            return null;
        };
    }
}
