<?
require "../../kernel/public.php";

ob_end_flush();

echo "testing email bounce\n";

mail(
	"support@awardwallet.com",
	"test bounce 1",
	"test bounce 1",
	"Content-Type: text/plain; charset=utf-8\nDate: ". date('r'). " \nFrom: vladimir@awardwallet.com\nReply-To: vladimir@awardwallet.com\nBcc: blabla@awardwallet.com"
);

mail(
	"support@awardwallet.com",
	"test bounce 2",
	"test bounce 2",
	"Content-Type: text/plain; charset=utf-8\nDate: ". date('r'). " \nFrom: vladimir@awardwallet.com\nReply-To: vladimir@awardwallet.com\nBcc: blabla@awardwallet.com\nReturn-Path: support@awardwallet.com"
);

