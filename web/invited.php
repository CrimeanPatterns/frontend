<?
require "kernel/public.php";
require($sPath."/schema/User.php");

if(isset($_SESSION['UserID'])){
    Redirect('/');
}

if (isset($_GET['refCode'])) {
	$refCode = addslashes(ArrayVal($_GET, 'refCode'));
	$qInviter = new TQuery("
	    SELECT
	        u.UserID,
	        u.FirstName,
	        u.LastName
	    FROM
	        Usr u
		WHERE
		    u.RefCode = '{$refCode}'");

    if ($qInviter->EOF) {
        Redirect("/");
    }
} elseif(isset($_GET['invId'])) {
	$invId = intval(ArrayVal($_GET, 'invId'));
	$qInviter = new TQuery("
	    SELECT
	        u.FirstName,
	        u.LastName,
	        u.UserID,
	        i.*
	    FROM
	        Invites i,
	        Usr u
	    WHERE
	        i.InvitesID = {$invId} AND
	        i.InviterID = u.UserID");

	if ($qInviter->EOF
    || ($qInviter->Fields["InviteeID"] != "")
	|| (isset($_GET['code']) && $qInviter->Fields['Code'] != ArrayVal($_GET, 'code'))){
        Redirect("/");
    }
}

if(isset($qInviter->Fields['UserID'])){
    $_SESSION['invrId'] = $qInviter->Fields['UserID'];
    $_SESSION['ref'] = 4;
}

Redirect("/?Register=1");
