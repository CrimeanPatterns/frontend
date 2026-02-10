<?php

require_once __DIR__ . '/BaseOfferPlugin.php';

class UnredeemedonecardcreditsOfferPlugin extends BaseOfferPlugin
{
    public function searchUsers()
    {
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

        $batch = [];

        foreach ($q as $r) {
            if ($u % 100 === 0) {
                set_time_limit(59);
            }

            // TODO: move this to offer schema
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
            $this->log(($u += count($batch)) . ' users so far...');
            flush();
        }

        return $u;
    }

    public function checkUser($userId, $offerUserId)
    {
        $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        return
            $user
            && (getRepository(\AwardWallet\MainBundle\Entity\Onecard::class)->OneCardsCountByUser($userId)["Left"] > 0);
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
            'onecardsCount' => getRepository(\AwardWallet\MainBundle\Entity\Onecard::class)->OneCardsCountByUser($userId)["Left"],
            'userName' => $user->getShortName(),
        ];
    }
}
