<?
require __DIR__.'/../../web/kernel/public.php';

$accountId = intval(ArrayVal(getopt('a:'), 'a'));
$q = new TQuery("select * from Account where AccountID = $accountId");
if($q->EOF)
	die("account $accountId not found\n");

$pass = DecryptPassword($q->Fields['Pass']);
echo "raw pass: ".$pass."\n";
echo "hex raw pass: ".StrToHex($pass)."\n";

echo "cleaning..\n";
$pass = CleanXMLValue($pass);
echo "raw pass: ".$pass."\n";
echo "hex raw pass: ".StrToHex($pass)."\n";

echo "trimming..\n";
$pass = trim($pass, "\x00..\x1F");
echo "hex raw pass: ".StrToHex($pass)."\n";

echo "done\n";
