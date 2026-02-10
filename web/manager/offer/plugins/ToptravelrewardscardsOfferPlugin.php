<?

require_once (__DIR__.'/../OfferPlugin.php');

class TopTravelRewardsCardsOfferPlugin extends OfferPlugin {

	public function searchUsers(){
		$u = 0;
		$this->log('Setting time limit to 59 seconds...');
		flush();
		set_time_limit(59);
		$this->log('Searching for users...');
		flush();
		$q = new TQuery('SELECT UserID FROM Usr WHERE UserID > ' . $this->getLastUserId());
		set_time_limit(59);
		$this->log('Adding users...');
		flush();
		foreach ($q as $r){
			$this->addUser($r['UserID'], array());
			$u++;
			if ($u % 100 == 0){
				set_time_limit(59);
				$this->log($u.' users so far...');
				flush();
			}
		}
		return $u;
	}
}
