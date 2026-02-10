#!/usr/bin/php
<?
require "../web/kernel/public.php";

$sql = "ALTER TABLE `EliteLevel`  ADD COLUMN `Name` VARCHAR(50) NULL AFTER `AllianceEliteLevelID`";

$Connection->Execute($sql);

for ($i = 1; $i < 506; $i++)
{
	$sql = "
		SELECT ValueText as VT
		FROM TextEliteLevel
		WHERE EliteLevelID = '".$i."'
		LIMIT 1
	";
	
	$OldName = new TQuery($sql);
	
	$newName = ucwords(strtolower($OldName->Fields['VT']));
	
	$sql = "
		UPDATE EliteLevel
		SET Name = '".$newName."'
		WHERE ElitelevelID = '".$i."'
	;";
		
	$Connection->Execute($sql);
}

$sql = "
	UPDATE EliteLevel
SET Name = 'Non-Elite'
WHERE EliteLevelID = '8';
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold Member' 
WHERE EliteLevelID = 37;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum Member' 
WHERE EliteLevelID = 38;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'GCCI' 
WHERE EliteLevelID = 45;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blue' 
WHERE EliteLevelID = 53;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 56;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 60;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Diamond' 
WHERE EliteLevelID = 64;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'HON Circle' 
WHERE EliteLevelID = 99;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Red' 
WHERE EliteLevelID = 100;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 101;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 102;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'National' 
WHERE EliteLevelID = 110;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'VIP' 
WHERE EliteLevelID = 120;
";

$Connection->Execute($sql);


$sql = "
	UPDATE EliteLevel 
SET Name = 'Dynasty' 
WHERE EliteLevelID = 147;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 148;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 162;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 163;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 170;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 171;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Black' 
WHERE EliteLevelID = 172;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Seeker' 
WHERE EliteLevelID = 173;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Enthusiast' 
WHERE EliteLevelID = 174;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Aficionado' 
WHERE EliteLevelID = 175;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Flying Returns' 
WHERE EliteLevelID = 176;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver Edge' 
WHERE EliteLevelID = 177;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold Edge' 
WHERE EliteLevelID = 178;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'The Maharajah' 
WHERE EliteLevelID = 179;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Entry' 
WHERE EliteLevelID = 187;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blue' 
WHERE EliteLevelID = 188;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 190;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 192;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 194;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Premier' 
WHERE EliteLevelID = 206;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Elite 75' 
WHERE EliteLevelID = 207;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 226;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 228;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 230;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Basic' 
WHERE EliteLevelID = 240;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'VIP' 
WHERE EliteLevelID = 246;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 248;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blue' 
WHERE EliteLevelID = 251;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 252;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 253;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 273;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 275;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blue' 
WHERE EliteLevelID = 276;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 277;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 278;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 329;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 331;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum Plus' 
WHERE EliteLevelID = 333;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Classic' 
WHERE EliteLevelID = 347;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Classic' 
WHERE EliteLevelID = 348;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Premium' 
WHERE EliteLevelID = 349;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Elite' 
WHERE EliteLevelID = 350;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'eLong' 
WHERE EliteLevelID = 351;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'VIP' 
WHERE EliteLevelID = 352;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Elite' 
WHERE EliteLevelID = 353;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = '1K' 
WHERE EliteLevelID = 356;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'MVP' 
WHERE EliteLevelID = 362;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'MVP Gold' 
WHERE EliteLevelID = 363;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'MVP Gold 75k' 
WHERE EliteLevelID = 364;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 366;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 367;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 368;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Member' 
WHERE EliteLevelID = 369;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Elite' 
WHERE EliteLevelID = 370;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Premium' 
WHERE EliteLevelID = 385;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Million Miler' 
WHERE EliteLevelID = 386;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Diamond' 
WHERE EliteLevelID = 391;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 393;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Bronze' 
WHERE EliteLevelID = 395;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Red' 
WHERE EliteLevelID = 401;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 402;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 403;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 404;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Member' 
WHERE EliteLevelID = 411;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 412;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 413;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 414;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blue Plus' 
WHERE EliteLevelID = 432;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 438;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 439;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Platinum' 
WHERE EliteLevelID = 440;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Top Platinum' 
WHERE EliteLevelID = 441;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 443;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 444;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold Elite' 
WHERE EliteLevelID = 445;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Green' 
WHERE EliteLevelID = 450;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 451;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Gold' 
WHERE EliteLevelID = 452;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Diamond' 
WHERE EliteLevelID = 453;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'ByRequest' 
WHERE EliteLevelID = 459;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Super Flyers' 
WHERE EliteLevelID = 477;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Standard' 
WHERE EliteLevelID = 478;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Silver' 
WHERE EliteLevelID = 479;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Golden' 
WHERE EliteLevelID = 480;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'PPS Club' 
WHERE EliteLevelID = 484;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Solitaire PPS Club' 
WHERE EliteLevelID = 485;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Preference' 
WHERE EliteLevelID = 493;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Privilege' 
WHERE EliteLevelID = 494;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Premier' 
WHERE EliteLevelID = 495;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Classique' 
WHERE EliteLevelID = 496;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'Blanche' 
WHERE EliteLevelID = 497;
";

$Connection->Execute($sql);

$sql = "
	UPDATE EliteLevel 
SET Name = 'VIB' 
WHERE EliteLevelID = 501;
";

$Connection->Execute($sql);


?>
