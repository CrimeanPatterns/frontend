<?php

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

require_once __DIR__ . '/BaseNewDesignOfferPlugin.php';

class AwardwalletplussubscriptionOfferPlugin extends BaseNewDesignOfferPlugin
{
    public function searchUsers()
    {
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();

        $lastUserId = $this->getLastUserId();
        $q = new TQuery("
            SELECT 
                UserID 
            FROM 
                Usr 
            WHERE 
                UserID > {$lastUserId} and 
                LogonCount >= 50 and
                Subscription is null and
                AccountLevel = 1
        ");

        set_time_limit(59);
        $this->log('Adding users...');
        flush();

        $batch = [];

        foreach ($q as $r) {
            if ($u % 100 === 0) {
                set_time_limit(59);
            }

            $batch[] = [$r['UserID'], []];
            $u++;

            if ($u % 1000 === 0) {
                $this->addUsers($batch);
                $batch = [];
                $this->log($u . ' users so far...');
                flush();
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
        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        return
            ($user->getLogoncount() >= 50)
            && !$user->isAwPlus()
            && !$user->getSubscription();
    }

    public function getParams($offerUserId, $preview = false, $params = null)
    {
        $query = new TQuery('SELECT UserID FROM OfferUser WHERE OfferUserID = ' . (int) $offerUserId);

        if ($query->EOF) {
            return [];
        }

        $userId = (int) $query->Fields['UserID'];
        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        if (!$user) {
            return [];
        }

        return [
            'logonCount' => $user->getLogoncount(),
            'registrationDate' => getSymfonyContainer()->get(LocalizeService::class)->formatDate($user->getCreationdatetime(), 'long'),
            'userName' => $user->getShortName(),
            'awPlusPrice' => \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::PRICE,
        ];
    }
}
