#!/usr/bin/php
<?php

require __DIR__ . "/../../web/kernel/public.php";

echo "update number accounts\n";

$q = new TQuery("
SELECT p.ProviderID,
       COALESCE(stat.Accounts, 0) AS Accounts
FROM   Provider p
       LEFT OUTER JOIN
              (SELECT  ProviderID,
                       COUNT(AccountID) AS Accounts
              FROM     Account
              GROUP BY ProviderID
              )
              stat
       ON     stat.ProviderID = p.ProviderID
");
$n = 0;

while (!$q->EOF) {
    $Connection->Execute("update Provider set Accounts = " . $q->Fields['Accounts'] . " where ProviderID = " . $q->Fields['ProviderID'] . "");
    $n++;
    $q->Next();
}

echo "updated $n providers\n";

echo "update number accounts in booking requests\n";

$q = new TQuery("
    SELECT p.ProviderID,
      COALESCE(stat.Accounts, 0) AS Accounts
    FROM   Provider p
           LEFT OUTER JOIN
                  (SELECT
                    p.ProviderID, count(*) AS Accounts
                    FROM Provider p
                        JOIN Account a ON a.ProviderID = p.ProviderID
                        JOIN AbAccountProgram aap ON aap.AccountID = a.AccountID
                    GROUP BY p.ProviderID
                  )
                  stat
           ON     stat.ProviderID = p.ProviderID
");
$n = 0;

while (!$q->EOF) {
    $Connection->Execute("update Provider set AbAccounts = " . $q->Fields['Accounts'] . " where ProviderID = " . $q->Fields['ProviderID'] . "");
    $n++;
    $q->Next();
}

echo "updated $n providers\n";

echo "done\n";
