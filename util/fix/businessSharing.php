#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";
require __DIR__."/../../web/lib/3dParty/Cli/Cli.php";

$cliHelp = new CliHelp();
$cliHelp->setUsageScript('businessSharing.php')
    ->setCLIParams(
        array(
            'test' => array(
                'short' 	=> 't',
                'desc'		=> 'Test mode. Will not be made any changes to the database',
                'default'	=> false,
            ),
            'color' => array(
                'short' 	=> 'c',
                'desc'		=> 'Color mode',
                'default'	=> false,
            ),
        )
    )
    ->setExample('php businessSharing.php -u 12345');

$cli = new Cli($cliHelp, false);
$result = $cli->validate();
$input = $cli->getInput();

$cli->setColorMode($input['color']);

if ($input['help'] || is_array($result)) {
    echo $cliHelp;
    if (is_array($result)) {
        $cli->addError($result);
    }
    exit();
}

$q = new TQuery("
    SELECT
        'A' AS Kind,
        ash.AccountShareID AS ShareID,
        a.AccountID AS ID,
        COALESCE(p.DisplayName, 'Unknown') AS DisplayName,
        u.UserID AS fromID,
        u.Company AS fromName,
        u2.UserID AS toID,
        CONCAT(u2.FirstName, ' ', u2.LastName ) AS toName
    FROM
        Account a
        LEFT OUTER JOIN Provider p ON p.ProviderID = a.ProviderID
        JOIN AccountShare ash ON ash.AccountID = a.AccountID
        JOIN UserAgent ua ON ua.UserAgentID = ash.UserAgentID
        JOIN Usr u ON u.UserID = ua.ClientID
        JOIN Usr u2 ON u2.UserID = ua.AgentID
    WHERE
        a.UserID = u.UserID
        AND u.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS."
        AND u2.AccountLevel <> ".ACCOUNT_LEVEL_BUSINESS."

    UNION ALL

    SELECT
        'C' AS Kind,
        pcsh.ProviderCouponShareID AS ShareID,
        pc.ProviderCouponID AS ID,
        pc.ProgramName AS DisplayName,
        u.UserID AS fromID,
        u.Company AS fromName,
        u2.UserID AS toID,
        CONCAT(u2.FirstName, ' ', u2.LastName ) AS toName
    FROM
        ProviderCoupon pc
        JOIN ProviderCouponShare pcsh ON pcsh.ProviderCouponID = pc.ProviderCouponID
        JOIN UserAgent ua ON ua.UserAgentID = pcsh.UserAgentID
        JOIN Usr u ON u.UserID = ua.ClientID
        JOIN Usr u2 ON u2.UserID = ua.AgentID
    WHERE
        pc.UserID = u.UserID
        AND u.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS."
        AND u2.AccountLevel <> ".ACCOUNT_LEVEL_BUSINESS."

    ORDER BY fromID, toID
");
if (!$q->EOF) {
    $i = 0;
    foreach($q as $fields) {
        $f = ($fields['Kind'] == 'A') ? 'Account' : 'Coupon';
        $cli->Log("{$f}: {$fields['fromName']} - {$fields['toName']} ({$fields['toID']}) - \"{$fields['DisplayName']}\" ({$fields['ID']})\n");
        if (!$input['test']) {
            if ($fields['Kind'] == 'A')
                $Connection->Execute("DELETE FROM AccountShare WHERE AccountShareID = {$fields['ShareID']}");
            else
                $Connection->Execute("DELETE FROM ProviderCouponShare WHERE ProviderCouponShareID = {$fields['ShareID']}");
        }
        $i++;
    }
    $cli->addGoodEvent("Found accounts: $i");
} else {
    $cli->addGoodEvent("Found accounts: 0");
}

$cli->Log("set ShareByDefault = 0 where ClientID - business account\n");
if (!$input['test']) {
    $Connection->Execute("
        UPDATE
            UserAgent ua
            JOIN Usr u ON u.UserID = ua.ClientID
        SET ua.ShareByDefault = 0
        WHERE
            u.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS."
    ");
}

$cli->Log("remove sharing where Account.UserID <> UserAgent.ClientID\n");
$q = new TQuery("
    SELECT
        'A' AS Kind,
        ash.AccountShareID AS ShareID,
        a.AccountID AS ID,
        case when u3.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u3.Company else concat( u3.FirstName, ' ', u3.LastName ) end AS AccountOwner,
        COALESCE(p.DisplayName, 'Unknown') AS DisplayName,
        u.UserID AS fromID,
        case when u.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u.Company else concat( u.FirstName, ' ', u.LastName ) end AS fromName,
        u2.UserID AS toID,
        case when u2.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u2.Company else concat( u2.FirstName, ' ', u2.LastName ) end AS toName
    FROM
        Account a
        LEFT OUTER JOIN Provider p ON p.ProviderID = a.ProviderID
        JOIN AccountShare ash ON ash.AccountID = a.AccountID
        JOIN UserAgent ua ON ua.UserAgentID = ash.UserAgentID
        JOIN Usr u ON u.UserID = ua.ClientID
        JOIN Usr u2 ON u2.UserID = ua.AgentID
        JOIN Usr u3 ON u3.UserID = a.UserID
    WHERE
        a.UserID <> ua.ClientID

    UNION ALL

    SELECT
        'C' AS Kind,
        pcsh.ProviderCouponShareID AS ShareID,
        pc.ProviderCouponID AS ID,
        case when u3.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u3.Company else concat( u3.FirstName, ' ', u3.LastName ) end AS AccountOwner,
        pc.ProgramName AS DisplayName,
        u.UserID AS fromID,
        case when u.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u.Company else concat( u.FirstName, ' ', u.LastName ) end AS fromName,
        u2.UserID AS toID,
        case when u2.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then u2.Company else concat( u2.FirstName, ' ', u2.LastName ) end AS toName
    FROM
        ProviderCoupon pc
        JOIN ProviderCouponShare pcsh ON pcsh.ProviderCouponID = pc.ProviderCouponID
        JOIN UserAgent ua ON ua.UserAgentID = pcsh.UserAgentID
        JOIN Usr u ON u.UserID = ua.ClientID
        JOIN Usr u2 ON u2.UserID = ua.AgentID
        JOIN Usr u3 ON u3.UserID = pc.UserID
    WHERE
        pc.UserID <> ua.ClientID

    ORDER BY fromID, toID
");

if (!$q->EOF) {
    $i = 0;
    foreach($q as $fields) {
        $f = ($fields['Kind'] == 'A') ? 'Account' : 'Coupon';
        $cli->Log("{$f}: (Owner \"{$fields['AccountOwner']}\") {$fields['fromName']} - {$fields['toName']} ({$fields['toID']}) - \"{$fields['DisplayName']}\" ({$fields['ID']})\n");
        if (!$input['test']) {
            if ($fields['Kind'] == 'A')
                $Connection->Execute("DELETE FROM AccountShare WHERE AccountShareID = {$fields['ShareID']}");
            else
                $Connection->Execute("DELETE FROM ProviderCouponShare WHERE ProviderCouponShareID = {$fields['ShareID']}");
        }
        $i++;
    }
    $cli->addGoodEvent("Found bad sharing: $i");
} else {
    $cli->addGoodEvent("Found bad sharing: 0");
}

$cli->addGoodEvent("done.");
