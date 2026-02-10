<?php

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\EmptyAccounts;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

$schema = "providerStatus";

require "../start.php";
drawHeader("Fake Accounts");

set_time_limit(1000);

class fakeAccounts
{
    protected $emailStatus = [
        EMAIL_UNVERIFIED => "Unverified",
        EMAIL_VERIFIED => "Verified",
        EMAIL_NDR => "NDR"];

    public function getFakeAccounts()
    {
        $sql = "
        SELECT
            um.UserID,
            um.FirstName,
            um.LastName,
            um.Email,
            um.EmailVerified,
            um.RegistrationIP,
            um.CreationDateTime,
            utc.TotalCount,
            utc.CreateTimeInGroup,
            IF(amuID.countAccounts > 0, amuID.countAccounts, 0) cAccounts,
            uf.UserID childUserID,
            uf.FirstName childFirstName,
            uf.LastName childLastName,
            uf.Email childEmail,
            uf.EmailVerified childEmailVerified,
            uf.RegistrationIP childRegistrationIP,
            uf.cAccounts childcAccounts,
            uf.CreationDateTime childCreationDateTime
        FROM
            Usr um
            JOIN(
                SELECT 
                    u.UserID,
                    u.FirstName,
                    u.LastName,
                    u.Email,
                    u.EmailVerified,
                    u.RegistrationIP,
                    u.CreationDateTime,
                    i.InviterID,
                    IF(auID.countAccounts > 0, auID.countAccounts, 0) cAccounts
                FROM 
                    Usr u
                    JOIN(
                        SELECT 
                            RegistrationIP, 
                            COUNT(RegistrationIP) countIPs
                        FROM 
                            Usr        
                        GROUP BY RegistrationIP
                        HAVING CountIPs > 2
                    ) cIPs ON (cIPs.RegistrationIP = u.RegistrationIP AND cIPs.CountIPs > 2)
                    LEFT JOIN Invites i ON (i.InviteeID = u.UserID AND i.InviterID IS NOT NULL)
                    LEFT JOIN (
                        SELECT 
                            COUNT(AccountID) countAccounts,
                            UserID
                        FROM
                            Account
                        GROUP BY 
                            UserID
                    ) auID ON (auID.UserID = u.UserID)
                WHERE 
                    u.CameFrom = 4
            ) uf ON (um.UserID = uf.InviterID AND uf.cAccounts = 0)
            LEFT JOIN (
                SELECT 
                    COUNT(AccountID) countAccounts,
                    UserID
                FROM
                    Account
                GROUP BY 
                    UserID
            ) amuID ON (amuID.UserID = um.UserID)
            LEFT JOIN(
                SELECT
                    um.UserID,	
                    COUNT(uf.UserID) TotalCount,
                    MAX(uf.CreationDateTime) CreateTimeInGroup
                FROM
                    Usr um
                    JOIN(
                        SELECT 
                            u.UserID,
                            u.FirstName,
                            u.LastName,
                            u.Email,
                            u.RegistrationIP,
                            u.CreationDateTime,
                            i.InviterID,
                            IF(auID.countAccounts > 0, auID.countAccounts, 0) cAccounts
                        FROM 
                            Usr u
                            JOIN(
                                SELECT 
                                    RegistrationIP, 
                                    COUNT(RegistrationIP) countIPs
                                FROM 
                                    Usr        
                                GROUP BY RegistrationIP
                                HAVING CountIPs > 2
                            ) cIPs ON (cIPs.RegistrationIP = u.RegistrationIP AND cIPs.CountIPs > 2)
                            LEFT JOIN Invites i ON (i.InviteeID = u.UserID AND i.InviterID IS NOT NULL)
                            LEFT JOIN (
                                SELECT 
                                    COUNT(AccountID) countAccounts,
                                    UserID
                                FROM
                                    Account
                                GROUP BY 
                                    UserID
                            ) auID ON (auID.UserID = u.UserID)
                        WHERE 
                            u.CameFrom = 4
                    ) uf ON (um.UserID = uf.InviterID AND uf.cAccounts = 0)
                    LEFT JOIN (
                        SELECT 
                            COUNT(AccountID) countAccounts,
                            UserID
                        FROM
                            Account
                        GROUP BY 
                            UserID
                    ) amuID ON (amuID.UserID = um.UserID)
                GROUP BY
                    um.UserID	
            ) utc ON (utc.UserID = um.UserID)
        WHERE 
            utc.TotalCount > 2
            AND uf.CreationDateTime >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
        ORDER BY
            utc.CreateTimeInGroup DESC,
            um.UserID, 
            uf.CreationDateTime DESC,
            uf.RegistrationIP
        ";
        $users = new TQuery($sql);

        if (!$users->EOF) {
            $newParent = 0;
            $i = -1;
            $accounts = [];

            while (!$users->EOF) {
                if ($newParent != $users->Fields['UserID']) {
                    $i++;
                    $y = -1;
                    $newParent = $users->Fields['UserID'];
                    $accounts[$i]['UserID'] = $users->Fields['UserID'];
                    $accounts[$i]['FirstName'] = $users->Fields['FirstName'];
                    $accounts[$i]['LastName'] = $users->Fields['LastName'];
                    $accounts[$i]['Email'] = $users->Fields['Email'];
                    $accounts[$i]['EmailVerified'] = ArrayVal($this->emailStatus, $users->Fields['EmailVerified'], 'n/a');
                    $accounts[$i]['CreationDateTime'] = $users->Fields['CreationDateTime'];
                    $accounts[$i]['RegistrationIP'] = $users->Fields['RegistrationIP'];
                    $accounts[$i]['cAccounts'] = $users->Fields['cAccounts'];
                    $accounts[$i]['TotalCount'] = $users->Fields['TotalCount'];
                }
                $y++;
                $accounts[$i]['Childs'][$y]['UserID'] = $users->Fields['childUserID'];
                $accounts[$i]['Childs'][$y]['FirstName'] = $users->Fields['childFirstName'];
                $accounts[$i]['Childs'][$y]['LastName'] = $users->Fields['childLastName'];
                $accounts[$i]['Childs'][$y]['Email'] = $users->Fields['childEmail'];
                $accounts[$i]['Childs'][$y]['EmailVerified'] = ArrayVal($this->emailStatus, $users->Fields['childEmailVerified'], 'n/a');
                $accounts[$i]['Childs'][$y]['RegistrationIP'] = $users->Fields['childRegistrationIP'];
                $accounts[$i]['Childs'][$y]['cAccounts'] = $users->Fields['childcAccounts'];
                $accounts[$i]['Childs'][$y]['CreationDateTime'] = $users->Fields['childCreationDateTime'];
                $users->Next();
            }

            return $accounts;
        }

        return [];
    }

    public function getFakeAccountsByUserID($userID)
    {
        $sql = "
		SELECT
			u.UserID,
			u.FirstName,
			u.LastName,
			u.Email,
			u.EmailVerified,
			u.RegistrationIP,
			u.CreationDateTime,
			IF(userStat.cAccounts > 0, userStat.cAccounts, 0) cAccounts,
			uc.UserID childUserID,
			uc.FirstName childFirstName,
			uc.LastName childLastName,
			uc.Email childEmail,
			uc.EmailVerified childEmailVerified,
			uc.RegistrationIP childRegistrationIP,
			uc.CreationDateTime childCreationDateTime,
			IF(childStat.childAccounts > 0, childStat.childAccounts, 0) childAccounts
		FROM
		    Usr u
		LEFT JOIN
		    Invites i ON u.UserID = i.InviterID
		LEFT JOIN
			Usr uc ON i.InviteeID = uc.UserID
		LEFT JOIN
			(
				SELECT
			 		count(AccountID) cAccounts,
			 		UserID
			 	FROM
			 		Account
			 	GROUP BY
			 		UserID
			) userStat on userStat.UserID = u.UserID
		LEFT JOIN
			(
				SELECT
			 		count(AccountID) childAccounts,
			 		UserID
			 	FROM
			 		Account
			 	GROUP BY
			 		UserID
			) childStat on childStat.UserID = uc.UserID
		WHERE
			u.UserID = {$userID} AND
			uc.UserID IS NOT NULL
		ORDER BY
			uc.CreationDateTime DESC,
			uc.RegistrationIP";
        $users = new TQuery($sql);

        if (!$users->EOF) {
            $newParent = 0;
            $i = -1;
            $accounts = [];

            while (!$users->EOF) {
                if ($newParent != $users->Fields['UserID']) {
                    $i++;
                    $y = -1;
                    $newParent = $users->Fields['UserID'];
                    $accounts[$i]['UserID'] = $users->Fields['UserID'];
                    $accounts[$i]['FirstName'] = $users->Fields['FirstName'];
                    $accounts[$i]['LastName'] = $users->Fields['LastName'];
                    $accounts[$i]['Email'] = $users->Fields['Email'];
                    $accounts[$i]['EmailVerified'] = ArrayVal($this->emailStatus, $users->Fields['EmailVerified'], 'n/a');
                    $accounts[$i]['CreationDateTime'] = $users->Fields['CreationDateTime'];
                    $accounts[$i]['RegistrationIP'] = $users->Fields['RegistrationIP'];
                    $accounts[$i]['TotalCount'] = 0;
                    $accounts[$i]['cAccounts'] = $users->Fields['cAccounts'];
                }
                $y++;
                $accounts[$i]['Childs'][$y]['UserID'] = $users->Fields['childUserID'];
                $accounts[$i]['Childs'][$y]['FirstName'] = $users->Fields['childFirstName'];
                $accounts[$i]['Childs'][$y]['LastName'] = $users->Fields['childLastName'];
                $accounts[$i]['Childs'][$y]['Email'] = $users->Fields['childEmail'];
                $accounts[$i]['Childs'][$y]['EmailVerified'] = ArrayVal($this->emailStatus, $users->Fields['childEmailVerified'], 'n/a');
                $accounts[$i]['Childs'][$y]['RegistrationIP'] = $users->Fields['childRegistrationIP'];
                $accounts[$i]['Childs'][$y]['cAccounts'] = $users->Fields['childAccounts'];
                $accounts[$i]['TotalCount']++;
                $accounts[$i]['Childs'][$y]['CreationDateTime'] = $users->Fields['childCreationDateTime'];
                $users->Next();
            }

            return $accounts;
        }

        return [];
    }

    public function getCouponsInviter($userID)
    {
        $sql = "
			SELECT
				Coupon.CouponID,
				Coupon.Code,
				Coupon.CreationDate,
				Cart.PayDate
			FROM
				Coupon
			LEFT OUTER JOIN Cart ON
			    Coupon.CouponID = Cart.CouponID AND
			    Cart.PayDate IS NOT NULL
			WHERE
				Coupon.Code LIKE 'Invite-{$userID}-%'
				AND Coupon.CreationDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
            ORDER BY
                Coupon.CreationDate,
                Cart.PayDate
		";
        $qCoupons = new TQuery($sql);
        $coupons = [];

        while (!$qCoupons->EOF) {
            $coupons[] = $qCoupons->Fields;
            $qCoupons->Next();
        }

        return $coupons;
    }
}

$deleteScriptUrl = "/manager/delete.php";

if (!isset($_GET['ids'])) {
    $inviter = ArrayVal($_GET, 'Inviter', 0);
    $fa = new fakeAccounts();
    $accounts = [];

    if (!empty($inviter)) {
        $accounts = $fa->getFakeAccountsByUserID(intval($inviter));
    } else {
        $accounts = $fa->getFakeAccounts();
    }
    ?>
<style>
/*subtables*/
table.level1{border-collapse:collapse; width:900px; margin-right:200px;}
	table.level1 th {background:#ddd; padding:3px 6px; border:1px solid #ccc;}
	table.level1 td {padding:3px 6px; border:1px solid #ccc; text-align:center;}
		table.level1 td a {font-size:11px;}

		table.level1 tr.level2 {padding:0; background:#fff; font-size:11px; display:none;}
		table.level1 tr.visible {display:table-row; background-color:#ffe7c9;}
		table.level1 tr.coupon {background-color:#C6E6ED;}
			table.level1 tr.level2 td {font-size:11px;}

table.level1 tr.hover {background:#eee;}
table.level1 tr.clickSelect1 {background:#ffa86e;}
table.level1 tr.selectedGroup {background:red;}
div.actionMenu {position:fixed; top:70px; left:1000px; width:200px; text-align:center; padding:10px 0; background:#e5e9ff;}
#selectBlock {margin:10px 0 0; border-top:1px dashed #ccc; padding:10px 0 0; text-align:left;}
    #selectBlock div {text-align:left; padding:0 0 0 15px;}
</style>
<form id="formFake" action="" style="width:1200px;">

<div class="actionMenu">
    <input name='SubmitFake' type="button" value="Delete Selected" />
    <div id="selectBlock">

    </div>
</div>
<table class='level1'>
    <tr>
        <th style='width:50px;'>#</th>
        <th style='width:50px;'>Select</th>
        <th style='width:60px;'>User ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
		<th>NDR</th>
        <th>Number of Accounts</th>
        <th>Registration IP</th>
        <th>Create</th>
        <th>Invite Coupons</th>
    </tr>
    <tr id='id<?php echo 978442; ?>'>
            <td>1<a name="<?php echo 978442; ?>"></a></td>
            <td>10
            </td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
            <td>wow</td>
    </tr>
    <?php
    $i = 1;

    foreach ($accounts as $a) {
        $coupons = $fa->getCouponsInviter($a['UserID']);
        ?>
        <tr id='id<?php echo $a['UserID']; ?>'>
            <td><?php echo $i; ?><a name="<?php echo $a['UserID']; ?>"></a></td>
            <td><a class='smallOpen'
                   href='javascript:showFake(<?php echo $a['UserID']; ?>)'>open(<?php echo $a['TotalCount']; ?>)</a>
            </td>
            <td><?php echo $a['UserID']; ?></td>
            <td><?php echo $a['FirstName']; ?></td>
            <td><?php echo $a['LastName']; ?></td>
            <td><?php echo $a['Email']; ?></td>
            <td><?php echo $a['EmailVerified']; ?></td>
            <td><?php echo $a['cAccounts']; ?></td>
            <td><?php echo $a['RegistrationIP']; ?></td>
            <td><?php echo $a['CreationDateTime']; ?></td>
            <td><?php echo count($coupons); ?></td>
    </tr>
        <?php
        if (!empty($coupons)) {
            ?>
            <tr rel='<?php echo $a['UserID']; ?>' class='level2 coupon'>
            <th></th>
            <th></th>
            <th colspan="7">Code</th>
            <th>Create</th>
            <th>Pay Date</th>
        </tr>
            <?php
            foreach ($coupons as $coupon) {
                ?>
                <tr rel='<?php echo $a['UserID']; ?>' class='level2 coupon'>
            <td></td>
                    <td style='width:50px;'><input type='checkbox' name='cou<?php echo $coupon['CouponID']; ?>'
                                                   value="<?php echo $coupon['CouponID']; ?>"/></td>
                    <td colspan="7"><?php echo $coupon['Code']; ?></td>
                    <td><?php echo $coupon['CreationDate']; ?></td>
                    <td><?php echo $coupon['PayDate']; ?></td>
        </tr>
                <?php
            }
        }
        $i++;

        foreach ($a['Childs'] as $ch) {
            ?>
            <tr rel='<?php echo $a['UserID']; ?>' class='level2'>
                <td></td>
                <td style='width:50px;'><input type='checkbox' name='sel<?php echo $ch['UserID']; ?>'
                                               value="<?php echo $ch['UserID']; ?>"/></td>
                <td><?php echo $ch['UserID']; ?></td>
                <td><?php echo $ch['FirstName']; ?></td>
                <td><?php echo $ch['LastName']; ?></td>
                <td><?php echo $ch['Email']; ?></td>
                <td><?php echo $ch['EmailVerified']; ?></td>
                <td><?php echo $ch['cAccounts']; ?></td>
                <td><?php echo $ch['RegistrationIP']; ?></td>
                <td><?php echo $ch['CreationDateTime']; ?></td>
        <td></td>
    </tr>
            <?php
        }
    }
    ?>
</table>
</form>
<script type='text/javascript'>
    scanAllGroups();
    function showFake(id){
        $('tr[id*=id]').removeClass('clickSelect1');
        $('tr#id'+id).addClass('clickSelect1');
        $('tr.level2').removeClass('visible');
        $('tr[rel='+id+']').addClass('visible');

        $('#selectBlock').html('');

        var ipsBl = $('tr[rel='+id+']');
        var ipCh = '';

        for(i = 0; i < ipsBl.length; i++){
            var ip = $(ipsBl[i]).children('td:eq(8)').html();
            if (ipCh != ip){
            	if(ip != null){
                	$('#selectBlock').append(addSelector(ip,id));
                	ipCh = ip;
            	}
            }
        }
    }

    function addSelector(ip,id){
        return "<div><input type='checkbox' onclick='selectIps("+id+",\""+ip+"\",this)' /> "+ip+"</div>";
    }

    function selectIps(id,ip,obj){
        var ipsBl = $('tr[rel='+id+']');
        for(i = 0; i < ipsBl.length; i++){
            if($(ipsBl[i]).children('td:eq(8)').html() == ip){
                $(ipsBl[i]).find('td:eq(1) input').attr('checked',$(obj).attr('checked'));
            }
        }
        scanGroup(id);
    }

    function scanAllGroups(){
        var blocks = $('tr[id*=id]');
        for (i = 0; i < blocks.length; i++){
            var id = $(blocks[i]).attr('id').substr(2);
            scanGroup(id);
        }
        //console.log(blocks);
    }

    function scanGroup(id){
        var chkInps = $('tr[rel='+id+'] td input:checked, tr[rel='+id+'] td[rel=del]');
        if (chkInps.length > 0) $('#id'+id).addClass('selectedGroup');
        else $('#id'+id).removeClass('selectedGroup');
    }

    $('input[name*=sel], input[name*=cou]').click(function(){
        var id = $(this).parent('td').parent('tr').attr('rel');
        scanGroup(id);

    });
    $('input[name=SubmitFake]').click(function(){
        var button = this;
//        button.disabled = true;
        var success = function (ask) {
            if (!ask || confirm('Are you sure?')) {
                var idsObj = $('input[name*=sel][type=checkbox]:checked');
                var couponsObj = $('input[name*=cou][type=checkbox]:checked');
                var ids = '';
                var coupons = '';
                for (i = 0; i < idsObj.length; i++) {
                    if ($(idsObj[i]).val() != '') {
                        ids += ',' + $(idsObj[i]).val();
                        $(idsObj[i]).parent('td').attr("rel", 'del');
                        $(idsObj[i]).parent('td').html("<span style='color:red'>del</span>");
                    }
                }
                for (i = 0; i < couponsObj.length; i++) {
                    if ($(couponsObj[i]).val() != '') {
                        coupons += ',' + $(couponsObj[i]).val();
                        $(couponsObj[i]).parent('td').attr("rel", 'del');
                        $(couponsObj[i]).parent('td').html("<span style='color:red'>del</span>");
                    }
                }
                if (ids == '' && coupons == '') alert('Select users or coupons please!');
                else {
                    ids = ids.substr(1);
                    coupons = coupons.substr(1);
                    //$('input[name=ID]').val(ids);
                    window.open('/manager/reports/fakeAccounts.php?ids=' + ids + '&coupons=' + coupons, 'newWindow');
                }
            }
        }
//        $.ajax({
//            url: "<?// =$deleteScriptUrl?>//",
//            cache: false,
//            timeout: 10000,
//            success: function (data, textStatus, xhr) {
//                var ask = true;
//                if ('Invalid request' !== data.trim()) {
//                    if (confirm("aw1 server has responded with unexpected result, contact the developer.\nDo you want to proceed?")) {
//                        ask = false;
//                    } else {
//                        button.disabled = false;
//                        return;
//                    }
//                }
//                button.disabled = false;
                success(true);
//            },
//            error: function (xhr, textStatus, errorThrown) {
//                alert('aw1 server is not available at the moment, try again later.');
//                button.disabled = false;
//            },
//        });

    });
    $('table.level1 tr').hover(
        function(){
            $(this).addClass('hover');
        },
        function(){
            $(this).removeClass('hover');
        }
    );
    <?php if (!empty($inviter)) { ?>
    showFake(<?php echo $inviter; ?>);
    <?php } ?>
</script>
    <?php
    drawFooter();
} else {
    $deletedUsers = [];
    $deletedCouponsByUserID = [];
    $deletedCoupons = [];
    $deletedCouponsIDs = [];
    $users = array_map('intval', explode(',', ArrayVal($_GET, 'ids', '')));
    $coupons = array_map('intval', explode(',', ArrayVal($_GET, 'coupons', '')));
    $deleteCallback = ArrayVal($_GET, 'deleteCallback', 0);

    if ($coupons) {
        $sql = "
            SELECT
                Coupon.CouponID,
                Coupon.Code,
                Coupon.CreationDate,
                Cart.PayDate,
                Cart.UserID
            FROM
                Coupon
            LEFT OUTER JOIN Cart ON
                Coupon.CouponID = Cart.CouponID AND
                Cart.PayDate IS NOT NULL
            WHERE
                Coupon.CouponID IN (" . implode(', ', $coupons) . ")
        ";
        $couponsQuery = new TQuery($sql);

        foreach ($couponsQuery as $coupon) {
            if (preg_match('/Invite-([0-9]+)-/ims', $coupon['Code'], $matches)) {
                $deletedCouponsByUserID[$matches[1]][] = $coupon['Code'];
                $deletedCoupons[] = $coupon;
                $deletedCouponsIDs[] = $coupon['CouponID'];
            }
        }

        if (!empty($deleteCallback)) {
            $cache = \Cache::getInstance();
            $deletedUsers = $cache->get('manager_fake_accounts_' . trim($deleteCallback));
            $mailer = getSymfonyContainer()->get('aw.email.mailer');
            $userRep = getSymfonyContainer()->get('doctrine.orm.default_entity_manager')
                ->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

            if (false !== $deletedUsers && is_array($deletedUsers)) {
                foreach ($deletedUsers as $userID => $userInfo) {
                    getSymfonyContainer()->get('logger')->info('Fake invitees removed', [
                        'inviter_int' => (int) $userID,
                        'invitess_list' =>
                            it($userInfo['Invitees'] ?? [])
                            ->map(fn (array $invitee) => (int) $invitee['UserID'])
                            ->toArray(),
                    ]);
                    /** @var \AwardWallet\MainBundle\Entity\Usr $inviter */
                    $inviter = $userRep->find($userID);
                    $template = new EmptyAccounts($inviter);
                    $template->invitees = array_map(function ($v) {
                        $v['CreationDateTime'] = date_create($v['CreationDateTime']);

                        return $v;
                    }, $userInfo['Invitees']);

                    if (isset($userInfo['Coupons'])) {
                        $template->coupons = $userInfo['Coupons'];
                    }
                    $message = $mailer->getMessageByTemplate($template);
                    $message->addBcc($mailer->getEmail('support'));
                    $mailer->send($message);

                    echo "<b>Mail sent to: " . $inviter->getEmail() . "</b><br/>";
                }
                $cache->set('manager_fake_accounts_' . trim($deleteCallback), [], 0);
            } else {
                exit("Deleted users data was not found, no emails will be sent");
            }
        } else {
            // sending emailsc
            if (!empty($users)) {
                $usersQuery = new TQuery("
                    SELECT
                        u.*,
                        IF(userStat.cAccounts > 0, userStat.cAccounts, 0) cAccounts,
                        i.InviterID
                    FROM Usr u
                    JOIN Invites i ON
                        u.UserID = i.InviteeID
                    LEFT JOIN(
                        SELECT
                            count(AccountID) cAccounts,
                            UserID
                        FROM
                            Account
                        GROUP BY
                            UserID
                    ) userStat on userStat.UserID = u.UserID
                    WHERE
                        u.UserID IN(" . implode(',', $users) . ")");

                while (!$usersQuery->EOF) {
                    $deletedUsers[$usersQuery->Fields['InviterID']]['Invitees'][] = $usersQuery->Fields;

                    if (!isset($deletedUsers[$usersQuery->Fields['InviterID']]['Inviter'])) {
                        $inviterQuery = new TQuery('SELECT * FROM Usr WHERE UserID = ' . $usersQuery->Fields['InviterID']);

                        if (!$inviterQuery->EOF) {
                            $deletedUsers[$usersQuery->Fields['InviterID']]['Inviter'] = $inviterQuery->Fields;
                        }
                    }
                    $usersQuery->Next();
                }
            }

            foreach ($deletedCouponsByUserID as $userID => $couponsArr) {
                if (isset($deletedUsers[$userID])) {
                    $deletedUsers[$userID]['Coupons'] = implode(', ', $couponsArr);
                }
            }

            $userIds = [];

            foreach ($deletedUsers as $deletedUser) {
                foreach ($deletedUser['Invitees'] as $invitee) {
                    $userIds[] = $invitee['UserID'];
                }
            }

            $couponIds = array_map(
                function ($couponId) {
                    return 'Coupon.' . $couponId;
                },
                $deletedCouponsIDs
            );

            if (!$userIds && !$couponIds) {
                exit('No entities was found for removal');
            }

            $cache = \Cache::getInstance();
            $cache->set('manager_fake_accounts_' . ($cacheKey = hash('sha256', RandomStr(0, 255, 32))), $deletedUsers, 24 * 3600);

            echo "<b id='prc'>Processing...  Please wait</b>";
            $router = getSymfonyContainer()->get('router');
            $finalBackTo = "{$_SERVER['SCRIPT_NAME']}?deleteCallback={$cacheKey}&ids=0&coupons=0";

            if ($couponIds) {
                $firstBackTo = $userIds ?
                    ($router->generate('aw_manager_delete_user') . "?UserID=" . \implode(',', $userIds) . "&BackTo=" . \urlencode($finalBackTo)) :
                    $finalBackTo;
                ?>
                <form name="users" action="<?php echo $deleteScriptUrl; ?>" method="post">
                    <input type="hidden" name="ID" value="<?php echo implode(',', $couponIds); ?>"/>
                    <input type="hidden" name="Schema" value="UserAdmin" />
                    <input type="hidden" name="ReturnFields" value="1" />
                    <input type="hidden" name="Script" value="fakeAccounts" />
                    <input
                       type="hidden"
                       name="BackTo"
                       value="<?php echo $firstBackTo; ?>"
                    />
                </form>
                <script type="text/javascript">
                    if($('input[name=ID]').val() == '')
                        $('#prc').html('users is not selected<br/>Complete!');
                    else
                        setTimeout('document.users.submit();',1000);
                </script>
            <?php } else { ?>
                <script type="text/javascript">
                    if($('input[name=ID]').val() == '')
                        $('#prc').html('users is not selected<br/>Complete!');
                    else
                        setTimeout(function () {
                            document.location = "<?php echo $router->generate('aw_manager_delete_user') . "?UserID=" . \implode(',', $userIds) . "&BackTo=" . \urlencode($finalBackTo); ?>";
                        },1000);
                </script>
            <?php }
            }
    }
}
