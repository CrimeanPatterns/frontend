<?php

require_once __DIR__ . '/../OfferPlugin.php';

class IhgbigwinOfferPlugin extends OfferPlugin
{
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
        $repAccount = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $connection = $this->doctrine->getConnection();
        $accountSql = $repAccount->getAccountsSQLByUser($userID, "AND (p.Code = 'ichotelsgroup')");
        $stmt = $connection->executeQuery($accountSql);
        $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $accountsData = [];

        foreach ($accounts as $account) {
            $row = [
                'Balance' => $repAccount->formatFullBalance($account['Balance'], $account['ProviderCode'], $account['BalanceFormat'], false),
                'UserName' => $account['UserName'],
                'AccountNumber' => $account['Login'],
                'AccountID' => $account['ID'],
            ];
            $accountsData[] = $row;
        }
        $result['Accounts'] = $accountsData;

        return $result;
    }

    public function searchUsers()
    {
        $u = 0;
        $this->log('Setting time limit to 59 seconds...');
        flush();
        set_time_limit(59);
        $this->log('Searching for users...');
        flush();
        $q = new TQuery("
            select distinct(UserID)
            from Account a
            join Provider p on a.ProviderID = p.ProviderID
            where p.Code = 'ichotelsgroup'
              and a.ErrorCode = 1
        ");
        set_time_limit(59);
        $this->log('Adding users...');
        flush();

        foreach ($q as $r) {
            $this->addUser($r['UserID'], []);
            $u++;

            if ($u % 100 == 0) {
                set_time_limit(59);
                $this->log($u . ' users so far...');
                flush();
            }
        }

        return $u;
    }
}
