#!/usr/bin/php
<?
require dirname(__FILE__)."/../web/kernel/public.php";
require "$sPath/agent/conversion.php";

$options = getopt("u:l:");

// input
if(!isset($options['u']) && !isset($options['l']))
	showUsage("missing -u or -l");
if(isset($options['u']))
	$q = new TQuery("select * from Usr where UserID = ".intval($options['u']));
if(isset($options['l']))
	$q = new TQuery("select * from Usr where Login = '".mysql_real_escape_string($options['l'])."'");
if($q->EOF)
	die("user not found\n");

personal2business($q->Fields);

function showUsage($error){
	die("error: {$error}
this script will convert user account from personal to business
usage:
	personal2business.php -u <user id> or -l <user login>
");
}
