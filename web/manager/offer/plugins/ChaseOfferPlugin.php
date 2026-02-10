<?php

require_once __DIR__ . '/../OfferPlugin.php';
require_once __DIR__. '/DeltaOfferPlugin.php';

class ChaseOfferPlugin extends OfferPlugin
{
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

        $batch = [];
   		foreach ($q as $r) {
            if ($u % 100 === 0) {
                set_time_limit(59);
            }

   			if (
                ($r['RegistrationIP'] && ($gi($r['RegistrationIP']) === 'US')) ||
                ($r['LastLogonIP']    && ($gi($r['LastLogonIP']) === 'US'))
            ) {
                $batch[] = [$r['UserID'], []];
   				$u++;
   				if ($u % 1000 === 0) {
                    $this->addUsers($batch);
                    $batch = [];
   					$this->log($u.' users so far...');
   					flush();
   				}
   			}
   		}

        if ($batch) {
            $this->addUsers($batch);
            $this->log(($u += count($batch)).' users so far...');
            flush();
        }

   		return $u;
   	}
}