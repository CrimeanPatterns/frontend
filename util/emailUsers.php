<?php
require "../web/kernel/public.php";

echo "searching users\n";
$q = new TQuery("select distinct
 u.Email, u.FirstName, u.LastName
from
 AccountProperty ap
 join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
 join Provider p on pp.ProviderID = p.ProviderID
 join Account a on ap.AccountID = a.AccountID
 join Usr u on a.UserID = u.UserID
where
 pp.Code in ('Coupons', 'Awards', 'Credits')
 and p.Code = 'rapidrewards'
 and ap.Val <> ''
 and ap.Val <> '0'
");
echo "mailing\n";

#$q->Fields['Email'] = "alexi@itlogy.com";
#$q->Fields['FirstName'] = "Alexi";
while(!$q->EOF){
	echo "{$q->Position}: {$q->Fields['Email']}\n";
	mailTo($q->Fields['Email'], 'AwardWallet is no longer getting your Southwest data including expirations', "Dear {$q->Fields['FirstName']}, we are writing to inform you that unfortunately Southwest is no longer allowing us to pull data from their website anymore. You can update your balance manually and you can use AwardWallet to auto-login to Southwest's website. From now on you need to track the expiration date of your Southwest miles manually.

Southwest on their website state the following: \"We like to think of ourselves as a Customer Service Company that happens to fly airplanes\". If you can kindly ask Southwest to stop disallowing AwardWallet to pull your reward info we are confident it could go a long way.

You can email (https://www.southwest.com/cgi-bin/feedbackEntry) or call Southwest at 1-800-I-FLY-SWA.

Thanks,
-AwardWallet Team
", EMAIL_HEADERS);
	$q->Next();
}
echo "done\n";
?>
