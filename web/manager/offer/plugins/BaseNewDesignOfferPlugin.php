<?php

require_once __DIR__ . '/BaseOfferPlugin.php';

class BaseNewDesignOfferPlugin extends BaseOfferPlugin
{
    /**
     * @var array
     */
    protected static $countries = ['US'];

    public function searchUsers()
    {
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();

        $q = new TQuery('
            SELECT 
                UserID, 
                RegistrationIP, 
                LastLogonIP 
            FROM 
                Usr 
            WHERE 
                UserID > ' . $this->getLastUserId() . ' AND
                InBeta = 1 AND
                BetaApproved = 1
        ');

        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        $gi = $this->getCountryByIpResolver();

        if (!static::$countries) {
            DieTrace("You MUST specify at least one country");
        }

        $batch = [];

        foreach ($q as $r) {
            if ($u % 100 === 0) {
                set_time_limit(59);
            }

            if (
                ($r['RegistrationIP'] && in_array($gi($r['RegistrationIP']), static::$countries, true))
                || ($r['LastLogonIP'] && in_array($gi($r['LastLogonIP']), static::$countries, true))
            ) {
                $batch[] = [$r['UserID'], []];
                $u++;

                if ($u % 1000 === 0) {
                    $this->addUsers($batch);
                    $batch = [];
                    $this->log($u . ' users so far...');
                    flush();
                }
            }
        }

        if ($batch) {
            $this->addUsers($batch);
            $this->log($u . ' users so far...');
            flush();
        }

        return $u;
    }

    public function checkUser($userId, $offerUserId)
    {
        $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        return $user;
    }
}
