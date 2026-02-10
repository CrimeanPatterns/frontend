<?
require_once( "$sPath/kernel/TAWTabs.php" );
$tabsId = 'onecardTabs';
$tabs = array(
	"front" => array(
		"caption" => "Front side",
		"path" => "#",
		"onclick" => "selectTab('{$tabsId}', 'front'); return false;",
		"selected" => true,
	),
	"back" => array(
		"caption" => "Back side",
		"path" => "#",
		"onclick" => "selectTab('{$tabsId}', 'back'); return false;",
		"selected" => false,
	),
);
$objTabs = new TAWTabs($tabs, 'front');
$objTabs->id = $tabsId;
?>
<div id="onecardPopup" style="display: none; width: 656px; height: 580px;" class="rowPopup roundedBox">
	<table cellpadding="0" cellspacing="0" class="frame frameTabs" style="width: 656px; ">
		<tbody><tr class="top">
			<td class="left">
			</td><td class="center fCenter">
				<a class="close" href="#" onclick="cancelPopup(); return false;"></a>
				<div class="program">
					AwardWallet OneCard sample image
				</div>
				<div class="clear" style="margin-bottom: 10px;"></div>
				<? $objTabs->drawTabs2(); ?>
			</td>
			<td class="right"></td>
		</tr>
		<tr class="middle">
			<td class="left"><div class="bg"></div></td>
			<td class="center fCenter">
				<div class="fCenterDiv" id="<?=$tabsId?>_content">
					<div id="<?=$tabsId?>_front">
						<img src="/images/onecardFront.png" style="margin: 5px 10px 0px; width: 600px; height: 380px;"/>
					</div>
					<div id="<?=$tabsId?>_back" style="display: none;">
						<img src="/images/onecardBack.png" style="margin: 5px 10px 0px; width: 600px; height: 380px;"/>
					</div>
				</div>
			</td>
			<td class="right"><div class="bg"></div></td>
		</tr>
		<tr class="bottom"><td class="left"></td><td class="center"></td><td class="right"></td></tr>
	</table>
</div>
