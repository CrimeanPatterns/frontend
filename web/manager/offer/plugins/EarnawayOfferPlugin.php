<?php

require_once __DIR__ . '/../OfferPlugin.php';

class EarnawayOfferPlugin extends OfferPlugin
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
        $accountSql = $repAccount->getAccountsSQLByUser($userID, "AND (p.Code = 'spg')");
        $stmt = $connection->executeQuery($accountSql);
        $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $accountsData = [];

        foreach ($accounts as $account) {
            if ($account['TableName'] != 'Coupon') {
                $aid = intval($account['ID']);
                $q = new TQuery("select Val from AccountProperty where AccountID = $aid and ProviderPropertyID = 50");

                if (!$q->EOF) {
                    $an = $q->Fields['Val'];
                } else {
                    $an = $account['Login'];
                }
                $row = [
                    'Balance' => $repAccount->formatFullBalance($account['Balance'], $account['ProviderCode'], $account['BalanceFormat'], false),
                    'UserName' => $account['UserName'],
                    'AccountNumber' => $an,
                    'AccountID' => $account['ID'],
                ];
                $accountsData[] = $row;
            }
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
            from Account a join Provider p on a.ProviderID = p.ProviderID where p.Code = 'spg' and a.ErrorCode = 1
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
