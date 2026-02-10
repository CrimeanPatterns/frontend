<?php

require_once __DIR__ . '/../OfferPlugin.php';

class CrossOfferPlugin extends OfferPlugin
{
    public function getParams($offerUserId, $preview = false, $params = null)
    {
        $result = parent::getParams($offerUserId, $preview, $params);
        // $userID = intval($_SESSION["UserID"]);
        $stmt = $this->doctrine->getConnection()->executeQuery("
			SELECT UserID FROM OfferUser where OfferUserID = ?",
            [$offerUserId],
            [\PDO::PARAM_INT]);
        $userID = $stmt->fetchColumn();

        if (!$userID) {
            return $result;
        }
        $spgAccounts = $this->getAccountsInfo($userID, 25);
        $deltaAccounts = $this->getAccountsInfo($userID, 7);
        $result["deltaAccounts"] = $deltaAccounts;
        $result["spgAccounts"] = $spgAccounts;

        return $result;
    }

    protected function getAccountsInfo($user, $provider)
    {
        $repAccount = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $connection = $this->doctrine->getConnection();
        $accountSql = $repAccount->getAccountsSQLByUser($user, "AND p.ProviderID = $provider");
        $stmt = $connection->executeQuery($accountSql);
        $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $accountsData = [];

        foreach ($accounts as $account) {
            $row = [
                'Balance' => $repAccount->formatFullBalance($account['Balance'], $account['ProviderCode'], $account['BalanceFormat'], false),
                'UserName' => $account['UserName'],
                'AccountNumber' => $repAccount->getAccountNumberByAccountID($account['ID']),
                'AccountID' => $account['ID'],
            ];
            $row['AccountNumber'] = !isset($row['AccountNumber']) ? $account['Login'] : $row['AccountNumber'];
            $accountsData[] = $row;
        }

        return $accountsData;
    }

    protected function searchUsers()
    {
        global $Connection;
        echo "Executing query...\n";

        if (php_sapi_name() != 'cli') {
            echo "<br />";
        }
        $sql = $q = new TQuery("
                 select distinct(Account.UserID) as u
                 from Account join Usr on Account.UserID = Usr.UserID
                 where Usr.UserID > {$this->getLastUserId()}
                 group by Account.UserID having
                 (select count(distinct(AccountID)) from Account where UserID = u and (ProviderID = 7)) > 0
                 and (select count(distinct(AccountID)) from Account where UserID = u and ErrorCode < 2 and (ProviderID = 25)) > 0
        ");
        $i = 0;

        foreach ($sql as $row) {
            $this->addUser($row['u'], []);
            $i++;
        }

        return "$i";
    }
}
