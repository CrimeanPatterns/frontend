<?
$schema = "admin";
require( "start.php" );

$id = intval($_GET['ID']);

$q = new TQuery("select * from Rental where RentalID = $id");
if($q->EOF){
	die("itinerary not found");
}
$fileName = "/var/log/www/awardwallet/tmp/hertz/rentals/".$q->Fields['Number'].".html";
if(!file_exists($fileName))
	die("file not found");

echo file_get_contents($fileName);
