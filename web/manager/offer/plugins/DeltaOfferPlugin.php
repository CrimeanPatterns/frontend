<?

require_once (__DIR__.'/../OfferPlugin.php');

class DeltaOfferPlugin extends OfferPlugin {

	public function searchUsers(){
		$u = 0;
		$this->log('Setting time limit to 59 seconds...');
		flush();
		set_time_limit(59);
		$this->log('Searching for users...');
		flush();
		$q = new TQuery('SELECT UserID, RegistrationIP, LastLogonIP FROM Usr WHERE UserID > ' . $this->getLastUserId());
		set_time_limit(59);
		$this->log('Adding users...');
		flush();
		$gi = $this->getCountryByIpResolver();
		foreach ($q as $r){
			if ($r['RegistrationIP'] and $gi($r['RegistrationIP']) == 'US'
					or $r['LastLogonIP'] and $gi($r['LastLogonIP']) == 'US') {
				$this->addUser($r['UserID'], array());
				$u++;
				if ($u % 100 == 0){
					set_time_limit(59);
					$this->log($u.' users so far...');
					flush();
				}
			}
		}
		return $u;
	}

	// #11062 - bug report
	// Seems that GeoIP::geoip_country_code_by_addr is buggy, it checks only ‘false’, but it is wrong.
	// Two examples:
	// 1) geoip_country_code_by_addr calls geoip_record_by_addr, which could return ‘0’;
	// 2) last mentioned function calls _get_record which could return ’NULL'
	// Actually it never returns 'false' and check from original function is useless
	// GeoIP::geoip_country_code_by_addr should be fixed (or http://php.net/manual/en/ref.geoip.php should be used
	// instead of GeoIP) and then calls of this function could be replaced with library ones
	public static function geoip_country_code_by_addr($gi, $addr) {
		if ($gi->databaseType == GEOIP_CITY_EDITION_REV1) {
			$record = geoip_record_by_addr($gi,$addr);
			if ( $record ) {
				return $record->country_code;
			}
		} else {
			$country_id = geoip_country_id_by_addr($gi,$addr);
			if ($country_id ) {
				return $gi->GEOIP_COUNTRY_CODES[$country_id];
			}
		}
	}
}
