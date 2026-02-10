<?

global $sBodyOnLoad, $leftMenu, $sharingMenu, $othersMenu, $topMenu, $manageTravelMenu, $metaDescription, $Interface, $cPage, $sTitle, $arSwitchText, $apacheHeaders;

$globalVars = getSymfonyGlobals();

StickToMainDomain();

if(!isset($metaDescription))
	$metaDescription = "AwardWallet.com allows you to track frequent flyer miles for FREE. You can search for cheap airfares, discuss and rate reward programs.";

if(!isset($topMenu))
	require("$sPath/design/topMenu/main.php");

$needSwitcher = (SITE_MODE == SITE_MODE_BUSINESS || (isset($_SESSION["HaveABusinessAccount"]) && $_SESSION["HaveABusinessAccount"]) || isset($_SESSION['SuccessfullConvert']) ||
                 isset($_SESSION['AdminOfBusinessAccount']));

if ($needSwitcher || (isset($topMenu["My Offers"]) && (!isset($topMenu["My Offers"]['count']) || $topMenu["My Offers"]['count'] == 0))) { // Hide "My Offers" button and shrink buttons area
    unset($topMenu["My Offers"]);
}

$Interface->Skin->restructureMenus();
$apacheHeaders = apache_request_headers();
if(!isset($apacheHeaders['Content-Only'])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<?
		if(!empty($Interface->NoExternals))
			echo '<meta name="robots" content="noindex,nofollow">';

			print "<title>$sTitle</title>";
	?>
	<meta name="keywords" content="Incentive reward center, Marriott reward, cheap airfare, reward incentive program,  American Express rewards, trip reward, air miles reward program, frequent flyer miles, track award program balances, air mile reward, award software, award program, delta sky miles, reward program reward network"/>
	<meta name="description" content="<?=$metaDescription?>"/>
	<?
	require "css.php";
	if (isset($Interface) && sizeof($Interface->HeaderStyles)) {
		echo "<style type=\"text/css\">\n";
		foreach ($Interface->HeaderStyles as $hstyle) {
			echo $hstyle."\n";
		}
		echo "</style>\n";
	}
	if(isset($Interface))
		echo implode("\n", $Interface->HeaderData);
	?>
	<? if(SITE_BRAND != SITE_BRAND_NONE) { ?>
	<link rel="canonical" href="<?=htmlspecialchars($_SERVER['REQUEST_SCHEME'] . 'awardwallet.com' . $_SERVER['REQUEST_URI'])?>" />
	<? } ?>
</head>

<body class="second<?
	if($cPage == "forum pages")
		echo " forum";
	if(!isset($_SESSION['UserID']))
		echo " notLoggedIn";
	if(SITE_MODE == SITE_MODE_BUSINESS)
		echo " business";
	if($Interface->SmallScreen)
		echo " smallScreen";
?>" id="body"<?if( $sBodyOnLoad != "" ) echo " onload=\"".htmlspecialchars($sBodyOnLoad)."\"";?>>
<?
if($needSwitcher) {
	if (isset($globalVars) && $globalVars->isBusinessMismanagementFromPersonal()) {
		$switchLink = '/security/switchSite?Goto='. urlencode('/agent/mismanagement.php');
	} else {
		$switchLink = '/security/switchSite';
	}
	echo "<a id='toPersonal' title='Switch to ".ucfirst(strtolower($arSwitchText[SITE_MODE]))."' href='{$switchLink}'>{$arSwitchText[SITE_MODE]}</a>";
}
?>
<table cellpadding="0" cellspacing="0" style="width: 100%;">
<colgroup>
	<col>
</colgroup>
<tbody>
<tr><td>
<div id="topBar">
<?
	if($cPage != "forum pages")
		$logo = getSymfonyContainer()->get("aw.manager.logo")->getLogo();
?>
	<div id="topBarLogo">
		<a id="logo<? if(!empty($logo)) echo $logo->class; ?>" class="second<? if(SITE_MODE == SITE_MODE_PERSONAL) { ?> new-year<? } ?>" href="/" alt="AwardWallet.com"<?=(!empty($logo->image)?' style="background-image:url(\'/'.$logo->image.'\')"':'') ?>></a>
		<? if(SITE_MODE == SITE_MODE_BUSINESS && empty($logo)) { ?>
		<a href="/" id="businessLogo"></a>
		<? } ?>
	</div>

	<div id="topButtons">
<?
foreach($topMenu as $key => $value)
    if($Interface->comparePaths($value["path"]))
    	$topMenu[$key]["selected"] = true;
if(isset($_SESSION['UserID'])){
	foreach($topMenu as $item){
		if(isset($item['topButton'])){
			?>
            <a href="<?=$item['path']?>"><div id="<?=$item['topButton']?>Button"<? if ($item['selected']) echo ' class="active button"'; else echo ' class="button"'; ?>><span><span><?=$item['caption']?></span></span></div><div class="<? if ($item['selected']) echo 'buttonRBactive'; else echo 'buttonRB'; ?>"></div></a>
            <?
			if(isset($item['count'])){
			?>
			<div class="number button"><?=$item['count']?></div>
			<?
		}
		if(isset($item['actionPath'])){
			?>
			<a href="<?=$item['actionPath']?>" title="<?=$item['actionCaption']?>"><div class="action button"><div></div></div></a>
			<?
			}
		}
	}
}
else{
?>
	<a href="#" onclick="showRegisterBox(); return false;"><div id="registerButton" class='active button'><span><span>Register</span></span></div></a>
	<a href="#" onclick="showLoginBox(); return false;"><div id="loginButton" class='active button'><span><span>Login</span></span></div></a>
<? } ?>
		<div id="secondMenu" <?=((SITE_MODE == SITE_MODE_BUSINESS && !isset($_SESSION['UserID']))?"style='margin-top:14px;'":"")?>>
			<?
			$i = 0;
			foreach($topMenu as $item){
				if(!isset($item['topButton'])){
					$selBegin = $aClass = $selEnd = "";
					if($item["selected"]){
						$selBegin='<div class="topMenuSelected"><div class="topMenuLeft"></div><div class="topMenuSelectedBg">';
						$selEnd='</div><div class="topMenuRight"></div></div>';
						$aClass = ' style="background-image: none; padding: 7px 0px 10px 0px;"';
					}
					$classes = "link";
					if(isset($item['count']))
						$classes .= " withCount";

			?>
					<a class="<?=$classes?><?=(isset($item['class'])?' '.$item['class']:'')?>" href="<?=$item['path']?>"<?=$aClass?>><?=$selBegin?><?=$item['caption']?><?=$selEnd?></a>
			<?

					if(isset($item['count'])){
						?>
						<div class="overallReferral" style="margin-right: <?=(count($topMenu)-1 == $i)?"0":"20"?>px;">
							<table cellpadding="0" cellspacing="0" class="overRefTable">
								<tr>
									<td><div class="referralLeft"></div></td>
									<td><? if(isset($item['actionPath'])) { ?><a href="<?=$item['actionPath']?>" class="leftMenuLink" style="text-decoration: none;"><? } ?><div class="referralCenter"><div class="referralUnderline"><?=$item['count']?></div></div><? if(isset($item['actionPath'])) { ?></a><? } ?></td>
									<td><div class="referralRight"></div></td>
								</tr>
							</table>
						</div>
						<?
					}
				}
				$i++;
			}
			?>
		</div>
	</div>
	<div class="clear"></div>
</div>

</td></tr>
<tr><td>

<div id="printHeader"><img src="/images/logo.png" alt="AwardWallet.com"/></div>

<?
if(($cPage != "forum pages") && isset($_SESSION['UserID']) && !NO_LEFT_MENU) {
?>
<div id="leftBar" class="notPrintable">

	<div class="box" id="userBox">
		<div class="bHead"></div>
<?
		if(isset($_SESSION['UserID'])) {
			$fixHeight = "";
			if(!isset($_SESSION['UserFields']['PictureVer']))
				$fixHeight = " style=\"height: 51px;\"";
?>
			<div class="bContent bPad" <?=$fixHeight?>>
				<a href='/user/edit.php#trPicture'><div class="photo"><?
				if(isset($_SESSION['UserFields']['PictureVer']))
					echo "<img src='".PicturePath("/images/uploaded/user", "small", $_SESSION['UserID'], $_SESSION['UserFields']['PictureVer'], $_SESSION['UserFields']['PictureExt'], "file")."'><div class='roundCorners64'></div>";
				?></div></a>
				Welcome back,<br/>
				<div class="name"><?
				if(SITE_MODE == SITE_MODE_BUSINESS)
					echo $_SESSION['UserFields']['Company'];
				else
					echo $_SESSION['UserName'];
				?></div>
				<div class="clear"></div>
			</div>
			<div class="contentEnd"></div>
			<div class="buttons">
				<a class="button" href="/security/logout">
				<div class="button">
					<div class="head"></div>
					<div class="caption">Logout</div>
					<div class="foot"></div>
				</div>
				</a>
				<a class="button" href="/user/edit.php">
				<div class="button">
					<div class="head"></div>
					<div class="caption">My Account</div>
					<div class="foot"></div>
				</div>
				</a>
			</div>
		<? } else { ?>
			<div class="bContent bPad">
				<div class="photo" style="background-position: 0 -509px;"></div>
				Welcome,<br/>
				<div class="name">please login</div>
				<div class="clear"></div>
			</div>
			<div class="contentEnd"></div>
			<div class="buttons" style="height: auto;">
				<a class="button" href="#" onclick="showLoginBox(); return false;">
				<div class="button">
					<div class="head"></div>
					<div class="caption">Login</div>
					<div class="foot"></div>
				</div>
				</a>
				<a class="button" href="#" onclick="showRegisterBox(); return false;">
				<div class="button">
					<div class="head"></div>
					<div class="caption">Register</div>
					<div class="foot"></div>
				</div>
				</a>
				<div class="clear"></div>
			</div>
		<? } ?>
		<div class="bFoot"></div>
	</div>

<?
	if(isset($leftMenu) && is_array($leftMenu)){
		openLeftBox(null, 'menu');
		makeDefaultSelection($leftMenu);
		drawLeftMenu($leftMenu);
		closeLeftBox();
	}

	if(isset($othersMenu) && is_array($othersMenu)){
		openLeftBox('Manage Rewards', 'menu');
		makeDefaultSelection($othersMenu);
		drawLeftMenu($othersMenu);
		closeLeftBox();
	}

	if(isset($manageTravelMenu)){
		openLeftBox('Manage Travel', 'menu');
		makeDefaultSelection($manageTravelMenu);
		drawLeftMenu($manageTravelMenu);
		closeLeftBox();
	}

	if(isset($sharingMenu) && is_array($sharingMenu)){
		makeDefaultSelection($sharingMenu);
		drawLeftMenu($sharingMenu);
	}
    if (SITE_MODE == SITE_MODE_PERSONAL) {
    	if(class_exists('TQuery') && isset($_SESSION['UserID'])){
    		openLeftBox('Invite to AwardWallet', 'menu');
    ?>
    		<div class="bContent">
    			<div class="bTopGrad bPad">
    <?
    		require( $sPath . "/lib/invite.php" );
    ?>
    			</div>
    		</div>
    <?
    		closeLeftBox();
    	}
    }

	if(function_exists('DrawLeftMenus'))
		DrawLeftMenus();
?>
<a class="oneCardBanner" href="/user/pay.php">
	<b>AwardWallet OneCard</b>
	<p>Get your personal <span>AwardWallet OneCard</span><br /> with all of your account numbers on it!</p>
</a>
</div>

<? } ?>

<div class='<? if($cPage == "forum pages") echo " forum"; ?> <? if(NO_LEFT_MENU) echo 'noLeftMenuPadd'; ?>' id="content">
<table id="contentTable" cellpadding="0" cellspacing="0"><tr><td id="contentTableCell"> <? // prevent clear: both from removing menu ?>
<?
}
?>