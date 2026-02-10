<?
require __DIR__.'/../kernel/public.php';

$hash = ArrayVal($_GET, 'ID');
if(empty($_SESSION['Files'][$hash]))
	die("No file found. May be your session has expired");

header('Content-Type: application/pdf');
readfile($_SESSION['Files'][$hash]);