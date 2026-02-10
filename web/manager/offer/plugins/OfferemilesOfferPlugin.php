<?php

require_once __DIR__ . '/../OfferPlugin.php';

class OfferemilesOfferPlugin extends OfferPlugin
{
    public function checkUser($userId, $offerUserId)
    {
        $query = $this->getQuery($userId);

        if ($query->EOF) {
            return false;
        }
        $user = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        if ($user) {
            if ($user->getCountryid()) {
                return $user->getCountryid() == 230;
            } else {
                $location = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->detectLocationByIP($_SERVER['REMOTE_ADDR']);

                return isset($location['Country']['ID']) && $location['Country']['ID'] == 230;
            }
        }

        return false;
    }

    public function getParams($offerUserId, $preview = false, $params = null)
    {
        $result = parent::getParams($offerUserId, $preview, $params);
        $stmt = $this->doctrine->getConnection()->executeQuery("
			SELECT UserID FROM OfferUser where OfferUserID = ?",
            [$offerUserId],
            [\PDO::PARAM_INT]);
        $userID = $stmt->fetchColumn();

        if (!$userID) {
            return $result;
        }
        $result = $this->getUserInfo($userID);

        return $result;
    }

    protected function getUserInfo($userID)
    {
        $repUsr = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $user = $repUsr->find($userID);
        // Zip/Postal Code
        $zip = $user->getZip();

        // detect Zip/Postal Code by IP
        if (empty($zip)) {
            $geoipResolver = getSymfonyContainer()->get('aw.geoip.country');

            try {
                if (
                    ($record = $geoipResolver->city($_SERVER['REMOTE_ADDR']))
                    && !empty($postal = $record->postal)
                ) {
                    $zip = $postal;
                }
            } catch (\Throwable $e) {
            }
        }
        $usrInfo = [
            // First name
            "FirstName" => $user->getFirstname(),
            // Last name
            "LastName" => $user->getLastname(),
            // E-mail address
            "Email" => $user->getEmail(),
            // Zip/Postal Code
            "Zip" => $zip,
            // Gender
            // Birthday
            // Password
            "Password" => str_replace(['[', ']', '^', '`', '_', '\\'], rand(0, 9), RandomStr(ord('A'), ord('z'), 10)),
        ];

        return $usrInfo;
    }

    protected function searchUsers()
    {
        echo "Executing query...\n";

        if (php_sapi_name() != 'cli') {
            echo "<br />";
        }
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();
        $sql = $this->getQuery();
        set_time_limit(59);
        $this->log('Adding users...');
        flush();
        $u = 0;

        foreach ($sql as $row) {
            $this->addUser($row['u'], []);

            if ($u % 100 == 0) {
                set_time_limit(59);
                $this->log($u . ' users so far...');
                flush();
            }
            $u++;
        }

        return "$u";
    }

    private function getQuery($user = null)
    {
        $filter = isset($user) ? " AND UserID = " . intval($user) . "" : "";

        return new TQuery("
            SELECT
                UserID AS u
            FROM
                Usr
            WHERE
                UserID > {$this->getLastUserId()} $filter
                AND 
                (select count(distinct(AccountID)) from Account where Account.UserID = Usr.UserID and ErrorCode < 2 and (ProviderID = 128)) = 0
        ");
    }
}
