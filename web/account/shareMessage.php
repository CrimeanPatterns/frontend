<?
require "../kernel/public.php";
require_once "../account/common.php";

define('TOTAL_WIDTH', 90);
define('NAME_WIDTH', 75);

AuthorizeUser();
header('Content-Type: application/json');
echo getShareMessage();

function getShareMessage(){
	global $sPath;
	$message = "I love AwardWallet"; /*checked*/
	$image = 'http://'.$_SERVER['HTTP_HOST'].'/images/wallet.png';
	if(isset($_SESSION['UserID'])){	
		$TotalBalance = getTotalBalance($_SESSION['UserID']);
		if(isset($_POST['Balances']) && is_array($_POST['Balances'])){
			GetAgentFilters($_SESSION['UserID'], 'All', $accountFilter, $couponFilter);
			$accounts = SQLToSimpleArray(AccountsSQL($_SESSION['UserID'], $accountFilter, "0 = 1", "", "0 = 1", "All"), "ID");
			foreach($_POST['Balances'] as $key => $value)
				if(in_array($key, $accounts))
					$TotalBalance += floatval($value);
		}
		if($TotalBalance != '0'){
			$total = number_format_localized($TotalBalance, 0);
			$message = "I have a total of ".$total." reward miles / points on AwardWallet.com"; /*checked*/
			$file = "stamps/".sprintf("%03d", time() % 100)."/".$_SESSION['UserID']."-".time().".png";
			$image = "/images/uploaded/".$file;
			MkDirs(dirname($sPath.$image));
			createStamp($image, $total);
			$image = "http://".$_SERVER['HTTP_HOST']."/cloud/".$file;
		}
	}
	return json_encode(array(
		"Message" => $message,
		"Image" => $image
	));
}

function createStamp($image, $total){
	global $sPath;
	$img = imagecreatefrompng($sPath."/images/stampEmpty.png");
	if($img === false)
		DieTrace("failed to load stamp");
	imagealphablending($img, true);
	imagesavealpha($img, true);
	$font = getSymfonyContainer()->getParameter("kernel.root_dir") . "/../data/fonts/arialbd.ttf";
	$whiteColor = imagecolorallocate($img, 255, 255, 255);
	$blackColor = imagecolorallocate($img, 0, 0, 0);
	$fontSize = 10;
	$angle = 0;

	$box = imagettfbbox($fontSize, $angle, $font, $total);
	$width = $box[4] - $box[0];
	$height = $box[3] - $box[7];
	imagettftext($img, $fontSize, $angle, (TOTAL_WIDTH - $width) / 2 + 1, 61 - ($height / 2) + 1, $blackColor, $font, $total);
	imagettftext($img, $fontSize, $angle, (TOTAL_WIDTH - $width) / 2, 61 - ($height / 2), $whiteColor, $font, $total);

	$fontSize = 9;
	$names = array($_SESSION['FirstName']." ".$_SESSION['LastName'], substr($_SESSION['FirstName'], 0, 1).".".$_SESSION['LastName'], $_SESSION['LastName'], substr($_SESSION['FirstName'], 0, 1).".".substr($_SESSION['LastName'], 0, 1).".");
	foreach($names as $name){
		$box = imagettfbbox($fontSize, $angle, $font, $name);
		$width = $box[4] - $box[0];
		$height = $box[3] - $box[7];
		if($width <= NAME_WIDTH)
			break;
	}
	imagettftext($img, $fontSize, $angle, (TOTAL_WIDTH - $width) / 2 + 5, 45 - ($height / 2), $whiteColor, $font, $name);

	imagepng($img, $sPath.$image);
//header( 'Content-Type: image/png' );
//header( "pragma: no-cache" );
//header( "cache-control: private" );
//imagepng($img);
	imagedestroy($img);
}
