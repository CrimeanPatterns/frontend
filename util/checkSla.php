<?
require "../web/kernel/public.php";

class checkSla {
    
    private $tier2;
	private $tier3;
	
	const R1 = 24;
	const R2 = 48;
	const R3 = 72;
	
	const S0 = 100;
	const UNK_ERRORS_COUNT = 30;
    
    public function init(){
        $this->calcTiers();
		$this->go();
		$this->deleteOldSlaEvents();
    }
    
    public function go(){
        
        $sql = "
        SELECT
			p.ProviderID, p.Code, p.Severity, p.ResponseTime, p.Tier,
			COUNT(a.AccountID) AS Popularity,	
			SUM(CASE WHEN a.ErrorCode = ".ACCOUNT_ENGINE_ERROR." AND a.UpdateDate > ADDDATE(NOW(), INTERVAL -4 HOUR) THEN 1 ELSE 0 END) LastUnkErrors,
			SUM(CASE WHEN a.AccountID IS NOT NULL AND a.UpdateDate > ADDDATE(NOW(), INTERVAL -4 HOUR) THEN 1 ELSE 0 END) LastChecked,
			e.SeverityDate, e.SlaEventSeverity, e.SlaEventTier, e.SlaEventEvent
		FROM
			Account a
			INNER JOIN Provider p ON a.ProviderID = p.ProviderID
			LEFT JOIN
			(
				SELECT 
					sa.ProviderID, 
					sa.EventDate AS SeverityDate,
					sa.NewSeverity AS SlaEventSeverity,
					sa.NewTier AS SlaEventTier,
					sa.Event AS SlaEventEvent
				FROM SlaEvent sa
				JOIN 
				(
					SELECT 
						ProviderID, 
						MAX(EventDate) edate
					FROM SlaEvent
					WHERE Event !='variation'
					GROUP BY ProviderID
				) tmpSla 
				ON (tmpSla.ProviderID = sa.ProviderID AND tmpSla.edate = sa.EventDate)
			) e
			ON p.ProviderID = e.ProviderID			
		WHERE
			p.State >= ".PROVIDER_ENABLED."
			AND p.WSDL = 1
			AND p.Assignee IS NULL
			AND a.UpdateDate > ADDDATE(NOW(), INTERVAL -1 DAY)
		GROUP BY
			p.ProviderID, p.Code, p.Severity, p.ResponseTime, p.Tier, e.SeverityDate, e.SlaEventSeverity, e.SlaEventTier, e.SlaEventEvent		
		ORDER BY
			Popularity DESC
		";
        
		echo "[".date("Y-m-d H:i:s",time())."] Query Started\n";		
        $q = new TQuery($sql);
		echo "[".date("Y-m-d H:i:s",time())."] Query Ended\n";
		
		while(!$q->EOF){
			
			$newTier = $this->setTier($q->Position);
			$event = 'variation';
			
			if (!empty($q->Fields['LastChecked']) && !empty($q->Fields['LastUnkErrors']))
				if(intval($q->Fields['LastChecked']) > 0) {
					$newSeverity = round(intval($q->Fields['LastUnkErrors'])/intval($q->Fields['LastChecked'])*100,2);					
				}
				else $newSeverity = 0;
			else
				$newSeverity = 0;
			
			$SeverityS = (empty($q->Fields['Severity']))?100:$q->Fields['Severity'];
			$newSeverityS = $this->setSeverity($newSeverity, intval($q->Fields['LastUnkErrors']));
			$eventSeverityS = empty($q->Fields['SlaEventSeverity'])?100:$q->Fields['SlaEventSeverity'];			
			
			echo date("Y-m-d H:i:s",time())." [{$q->Fields['Code']}] updated LastChecked:{$q->Fields['LastChecked']}, LastUE:{$q->Fields['LastUnkErrors']}\n";
			
			if ($q->Fields['SlaEventEvent'] == 'assign') {
				$newResponseTime = null;
				if ($newSeverityS == self::S0){
					$event = 'close';
					echo "\t response time has been closed\n";
				}
			} else {
				if ($newSeverityS < $SeverityS) {
					echo "\t changed severity from S$SeverityS to S$newSeverityS\n";
					if ($q->Fields['SlaEventEvent'] == 'start' || $q->Fields['SlaEventEvent'] == 'up') {
	
						if ($newSeverityS < $eventSeverityS) {
							$newResponseTime = self::setResponseTime($newTier, $newSeverityS);
							$event = 'up';
							echo "\t set new response time - ".$newResponseTime." hours\n";
						} else {
							$newResponseTime = self::calcDiffTime($q->Fields['SlaEventTier'], time(), strtotime($q->Fields['SeverityDate']), $eventSeverityS);					
							if ($newResponseTime == 0){
								$event = 'late';
								echo "\t response time has been ended (Late problem)\n";
							} else {
								echo "\t update response time to $newResponseTime\n";
							}						
						}
						
					} elseif($q->Fields['SlaEventEvent'] == 'close' || empty($q->Fields['SlaEventEvent'])) {
						if ($SeverityS == self::S0){
							$event = 'start';
							$newResponseTime = self::setResponseTime($newTier, $newSeverityS);
							echo "\t start new response time to $newResponseTime\n";
						} else
							echo "\t Lost event(close)\n";
								
					} else {
						$newResponseTime = 0;
					}					
				}
				else {
					if ($newSeverityS > $SeverityS)
						echo "\t changed severity from S$SeverityS to S$newSeverityS\n";
					if ($newSeverityS == self::S0){
						$newResponseTime = null;
						if ($newSeverityS != $SeverityS){
							$event = 'close';
							echo "\t response time has been closed\n";
						}
					} else {
						if ($q->Fields['SlaEventEvent'] != 'late') {
							$newResponseTime = self::calcDiffTime($q->Fields['SlaEventTier'], time(), strtotime($q->Fields['SeverityDate']), $eventSeverityS);
							if ($newResponseTime == 0){
								$event = 'late';
								echo "\t response time has been ended (Late problem)\n";
							} else {
								echo "\t update response time to $newResponseTime\n";
							}
						} else {
							$newResponseTime = 0;
						}
					}
				}
			}
			
			$SlaEventId = false;
			if ($SeverityS != $newSeverityS || ($event == 'late' && $q->Fields['SlaEventEvent'] != 'late')){
				$SlaEventId = $this->addSlaEvent($q->Fields['ProviderID'],$SeverityS,$newSeverityS,intval($q->Fields['Tier']),$newTier,intval($q->Fields['LastChecked']),intval($q->Fields['LastUnkErrors']),$event);
				if ($event != 'start' && $event != 'up')
					$SlaEventId = false;
			}				
							
			$this->updateSlaProvider($q->Fields['ProviderID'],$newTier,$newSeverityS,$newResponseTime,$SlaEventId);	
			
			$q->Next();
		}
        
    }
	
	static function calcDiffTime($Tier,$newTms,$oldTms,$Severity){
		if (empty($Tier)) DieTrace("Sla Event Tier is Empty");
		$hours = ceil(self::setResponseTime($Tier,$Severity) - ($newTms - $oldTms)/3600); 
		if ($hours > 0) $time = $hours;
		else $time = 0;
		return $time;
	}
	
	private function updateSlaProvider($ProviderID, $Tier, $Severity, $ResponseTime,$SlaEventId){		
		global $Connection;
		$Severity = $Severity == 100?'NULL':$Severity;
		$ResponseTime = ($ResponseTime == null && !isset($ResponseTime))?"NULL":$ResponseTime;
		if ($ResponseTime == 'NULL')
			$SlaEventId = 'NULL';
		$sql = "
			UPDATE
				Provider
			SET
				Tier = $Tier,
				Severity = $Severity,
				ResponseTime = $ResponseTime
				".((intval($SlaEventId)>0 || $SlaEventId == 'NULL')?",RSlaEventID=$SlaEventId":"")."
			WHERE
				ProviderID = $ProviderID
		";
		$Connection->Execute($sql);
	}
	
	private function addSlaEvent($ProviderID,$OldSeverity,$NewSeverity,$OldTier,$NewTier,$Checked,$Errors,$Event){
		global $Connection;	
		$sql = "
			INSERT INTO
				SlaEvent (ProviderID,EventDate,OldSeverity,NewSeverity,OldTier,NewTier,Checked,Errors,Event)
			VALUES ($ProviderID,NOW(),$OldSeverity,$NewSeverity,$OldTier,$NewTier,$Checked,$Errors,'$Event')
		";
		$Connection->Execute($sql);
		return $Connection->InsertID();
	}
	
	private function calcTiers(){
        $q = new TQuery("select count(*) as Cnt from Provider where WSDL = 1 and State >= ".PROVIDER_ENABLED);
		$this->tier2 = round($q->Fields["Cnt"] * 0.2);
		$this->tier3 = round($q->Fields["Cnt"] * 0.5);
    }
	
	private function setTier($position){
		$tier = 3;
		if($position < $this->tier3)
			$tier = 2;
		if($position < $this->tier2)
			$tier = 1;
		return $tier;
	}
	
	private function setSeverity($Severity,$Errors = false){
		$s = self::S0;
		if ($Errors >= self::UNK_ERRORS_COUNT || !$Errors) {
			if ($Severity >= 3 && $Severity < 10) $s = 3;
			if ($Severity >= 10 && $Severity < 50) $s = 2;
			if ($Severity >= 50) $s = 1;
		}
		
		return $s;
	}
	
	private static function setResponseTime($Tier,$Severity){
		$ResponseTime = null;
		switch($Tier){
			case 1:
				switch($Severity){
					case 1: 
					case 2: $ResponseTime = self::R1; break;
					case 3: $ResponseTime = self::R2; break;
				}
				break;
			case 2:
				switch($Severity){
					case 1: $ResponseTime = self::R1; break;
					case 2: $ResponseTime = self::R2; break;
					case 3: $ResponseTime = self::R3; break;
				}
				break;
			case 3:
				switch($Severity){
					case 1: $ResponseTime = self::R2; break;
					case 2: 
					case 3: $ResponseTime = self::R3; break;
				}
				break;
		}		
		return $ResponseTime;
	}
	
	public function deleteOldSlaEvents(){
		global $Connection;
		$sql = "
		DELETE FROM
			SlaEvent
		WHERE
			EventDate < ADDDATE(now(), INTERVAL -1 YEAR)
		";		
		$Connection->Execute($sql);
	}
    
}

$sla = new checkSla();
$sla->init();
echo "END\n";


?>
