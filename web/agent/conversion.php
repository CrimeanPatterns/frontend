<?php

function business2personal($fields)
{
    global $Connection;
    echo "converting user {$fields['FirstName']} {$fields['LastName']} ({$fields['UserID']}) from business to personal\n";
    $adminId = $fields['UserID'];
    $qLink = new TQuery("select u.UserID, ua.UserAgentID, ua.AccessLevel, u.Company, u.AccountLevel from UserAgent ua, Usr u
		where ua.ClientID = {$adminId} and ua.AgentID = u.UserID and u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS);

    if ($qLink->EOF) {
        exit("user is not admin of any business\n");
    }

    $businessId = $qLink->Fields['UserID'];
    $userAgentId = $qLink->Fields['UserAgentID'];
    echo "user is admin of business {$qLink->Fields['Company']} ({$businessId})\n";

    echo "stop sharing personal to business\n";

    foreach (["Account", "ProviderCoupon", "TravelPlan"] as $table) {
        $Connection->Execute("delete {$table}Share from {$table}Share, {$table}
		where {$table}Share.{$table}ID = {$table}.{$table}ID and {$table}Share.UserAgentID = {$userAgentId}
		and {$table}.UserID = {$adminId}");
    }

    echo "deleting link\n";
    $Connection->Execute("delete from UserAgent where ClientID = {$adminId} and AgentID = {$businessId}");
    $Connection->Execute("delete from UserAgent where ClientID = {$businessId} and AgentID = {$adminId}");

    echo "moving info from business to personal\n";
    $Connection->Execute("update UserAgent set ClientID = $adminId, AccessLevel = " . ACCESS_READ_BALANCE_AND_STATUS . " where ClientID = $businessId");
    $Connection->Execute("update UserAgent set AgentID = $adminId where AgentID = $businessId");

    foreach (["Account", "ProviderCoupon", "TravelPlan"] as $table) {
        $Connection->Execute("update {$table} set UserID = $adminId where UserID = $businessId and UserAgentID is not null");
    }

    foreach (["Trip", "Reservation", "Restaurant", "Direction", "Rental"] as $table) {
        $Connection->Execute("update {$table}, TravelPlan set {$table}.UserID = TravelPlan.UserID where TravelPlan.UserID = $adminId and {$table}.TravelPlanID = TravelPlan.TravelPlanID");
        $Connection->Execute("update {$table}, Account set {$table}.UserID = Account.UserID where Account.UserID = $adminId and {$table}.AccountID = Account.AccountID");
    }

    echo "deleting business account\n";
    $Connection->Execute("delete from Cart where UserID = {$businessId}");
    $Connection->Execute("delete from Usr where UserID = {$businessId}");

    echo "done\n";
}

function personal2business($fields, $owners = null, $shareAccounts = null, $companyName = null, $connectedUsers = null)
{
    global $Connection;

    echo "converting user {$fields['FirstName']} {$fields['LastName']} ({$fields['UserID']}) to business<br/>";
    $adminId = $fields['UserID'];
    $qLink = new TQuery("select u.UserID, ua.UserAgentID, ua.AccessLevel, u.Company, u.AccountLevel from UserAgent ua, Usr u
	where ua.AgentID = {$adminId} and ua.ClientID = u.UserID and u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . " AND ua.AccessLevel = " . ACCESS_ADMIN);

    if (!$qLink->EOF) {
        echo "user already admin of business {$qLink->Fields['Company']}<br/>";

        if ($qLink->Fields['AccessLevel'] != ACCESS_WRITE) {
            echo "fixing privileges<br/>";
            $Connection->Execute("update UserAgent set AccessLevel = " . ACCESS_WRITE . " where UserAgentID = " . $qLink->Fields['UserAgentID']);
        }
        $qLink = new TQuery("select ua.AccessLevel, ua.UserAgentID from UserAgent ua, Usr u
			where ua.AgentID = {$adminId} and ua.ClientID = u.UserID");

        if ($qLink->Fields['AccessLevel'] != ACCESS_ADMIN) {
            echo "fixing privileges<br/>";
            $Connection->Execute("update UserAgent set AccessLevel = " . ACCESS_ADMIN . " where UserAgentID = " . $qLink->Fields['UserAgentID']);
        }
    } else {
        // run
        echo "creating company account<br/>";
        $fields = array_keys($fields);
        $fields = array_combine($fields, $fields);
        unset($fields['UserID']);
        /** @var \AwardWallet\MainBundle\Entity\Repositories\UsrRepository $userRepo */
        $userRepo = getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $fields['Login'] = "'" . addslashes($userRepo->createLogin(0, $companyName)) . "'";
        $fields['Email'] = "'b." . $adminId . "@awardwallet.com'";
        $fields['RefCode'] = "'" . addslashes(RandomStr(ord('a'), ord('z'), 10)) . "'";
        $fields['SocialAdID'] = 'null';
        $fields['Pass'] = "'disabled'";
        $fields['EmailNewPlans'] = 0;
        $fields['EmailTCSubscribe'] = 0;
        $fields['EmailRewards'] = 0;
        $fields['EmailVerified'] = 0;
        $fields['CheckinReminder'] = 0;
        $fields['FirstName'] = "'Business'";
        $fields['LastName'] = "'Account'";
        $fields['MidName'] = 'null';
        $fields['Prefix'] = 'null';
        $fields['Suffix'] = 'null';
        $fields['ItineraryCalendarCode'] = 'null';
        $fields['AccountLevel'] = ACCOUNT_LEVEL_BUSINESS;

        if (!isset($companyName)) {
            $companyName = $fields['LastName'] . " Business";
        }
        $fields['Company'] = "'" . addslashes($companyName) . "'";
        $Connection->Execute("insert into Usr(" . implode(", ", array_keys($fields)) . ")
			select " . implode(", ", $fields) . " from Usr where UserID = {$adminId}");
        $businessId = $Connection->InsertID();
        echo "created business {$businessId}, he is admin now<br/>";
        echo "moving info from personal to business<br/>";

        $replaceAgents = [];

        if (!empty($connectedUsers)) {
            foreach ($connectedUsers as $ID => $Fields) {
                if (intval($ID) != 0) {
                    $replaceAgents['Connected'][] = $ID;
                } else {
                    $replaceAgents['Family'][] = $Fields['UserAgentID'];
                }
            }
        }

        // update connected user
        if (!empty($replaceAgents['Connected'])) {
            $replaceAgents['Connected'] = implode(',', $replaceAgents['Connected']);
            echo 'update Connected user for move to business (' . $replaceAgents['Connected'] . ')<br/>';
            $Connection->Execute("update UserAgent set ClientID = $businessId, ShareByDefault = 0, TripShareByDefault = 0, AccessLevel = " . ACCESS_NONE . " where ClientID = $adminId AND AgentID IN ({$replaceAgents['Connected']})");
            $Connection->Execute("update UserAgent set AgentID = $businessId, ShareByDefault = 0, TripShareByDefault = 0 where AgentID = $adminId AND ClientID IN ({$replaceAgents['Connected']})");
        }

        if (!empty($replaceAgents['Family'])) {
            $replaceAgents['Family'] = implode(',', $replaceAgents['Family']);
            echo 'update Family user for move to business (' . $replaceAgents['Family'] . ')<br/>';
            $Connection->Execute("update UserAgent set AgentID = $businessId, AccessLevel = " . ACCESS_NONE . " where UserAgentID IN ({$replaceAgents['Family']})");
        }

        if (isset($owners)) {
            echo "adding new owners<br/>";

            foreach ($owners as $userId) {
                echo "- owner = {$userId}<br/>";
                $Connection->Execute("update UserAgent set AccessLevel = " . ACCESS_ADMIN . ", ShareByDefault = 0 where AgentID = {$userId} and ClientID = {$businessId}");
            }
        }
        echo 'moving corporate and family accounts to business<br/>';
        $Connection->Execute("update Account, Provider set Account.UserID = $businessId, Account.userAgentID = IF(Provider.Corporate = 1,NULL,Account.userAgentID) 
							  where Account.ProviderID = Provider.ProviderID and " .
            "((Account.UserID = $adminId and Provider.Corporate = 1)" .
            ((!empty($replaceAgents['Family'])) ? " or (Account.UserID = $adminId and Account.UserAgentID IN({$replaceAgents['Family']}))" : "") .
            ")");

        if (!empty($replaceAgents['Family'])) {
            echo 'moving travel plans and Provider Coupons of family accounts<br/>';

            foreach (["ProviderCoupon", "TravelPlan"] as $table) {
                $Connection->Execute("update {$table} set UserID = $businessId where UserID = $adminId and UserAgentID IN ({$replaceAgents['Family']})");
            }

            foreach (["Trip", "Reservation", "Restaurant", "Direction", "Rental"] as $table) {
                $Connection->Execute("update {$table}, TravelPlan set {$table}.UserID = TravelPlan.UserID where TravelPlan.UserID = $businessId and {$table}.TravelPlanID = TravelPlan.TravelPlanID");
                $Connection->Execute("update {$table}, Account set {$table}.UserID = Account.UserID where Account.UserID = $businessId and {$table}.AccountID = Account.AccountID");
            }
        }

        echo "creating connection between admin and business account<br/>";
        $Connection->Execute(InsertSQL("UserAgent", [
            "ClientID" => $businessId,
            "AgentID" => $adminId,
            "AccessLevel" => ACCESS_ADMIN,
            "IsApproved" => 1,
            "ShareByDefault" => 0,
            "TripShareByDefault" => 0,
            "ShareCode" => "'" . addslashes(RandomStr(ord('a'), ord('z'), 10)) . "'",
        ]));
        $businessShareId = $Connection->InsertID();
        $Connection->Execute(InsertSQL("UserAgent", [
            "ClientID" => $adminId,
            "AgentID" => $businessId,
            "AccessLevel" => ACCESS_WRITE,
            "IsApproved" => 1,
            "ShareByDefault" => 0,
            "TripShareByDefault" => 0,
            "ShareCode" => "'" . addslashes(RandomStr(ord('a'), ord('z'), 10)) . "'",
        ]));
        $userAgentId = $Connection->InsertID();

        echo "sharing personal to business<br/>";

        foreach (["Account", "ProviderCoupon", "TravelPlan"] as $table) {
            $Connection->Execute("insert into {$table}Share({$table}ID, UserAgentID)
			select {$table}ID, $userAgentId from {$table} where UserID = {$adminId} AND UserAgentID IS NULL");
        }

        echo "sharing business accounts to admin<br/>";
        $q = new TQuery("select AccountID from Account where UserID = $businessId");

        while (!$q->EOF) {
            $Connection->Execute("insert into AccountShare(AccountID, UserAgentID)
			values({$q->Fields['AccountID']}, $businessShareId)");
            $q->Next();
        }

        if (isset($shareAccounts)) {
            echo "setting account rights<br/>";
            $q = new TQuery("select ash.* from AccountShare ash
			join UserAgent ua on ash.UserAgentID = ua.UserAgentID
			where ua.AgentID = {$businessId}");

            while (!$q->EOF) {
                if (!in_array($q->Fields['AccountID'], $shareAccounts)) {
                    echo "unsharing {$q->Fields['AccountID']}<br/>";
                    $Connection->Execute("delete from AccountShare where AccountShareID = {$q->Fields['AccountShareID']}");
                }
                $q->Next();
            }
        }
    }
    echo "done<br/>";

    return $businessId;
}
