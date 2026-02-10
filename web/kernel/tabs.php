<?
function drawTabs($tabs, $width="100%"){
	global $margin;
	if($margin == 0)
		$margin = 30;
	if(is_array($tabs)){
		$img2 = "<img src=\"/images/tabBorderNotSelectedL.gif\" width=\"4\" height=\"36\" alt=\"\">";
		if($tabs[0]["selected"])
			$img2 = "<img src=\"/images/tabBorderL.gif\" width=\"4\" height=\"36\" alt=\"\">";
		?>
		<table cellspacing="0" cellpadding="0" border="0" style="margin-top: 2px;" width="<?=$width?>">
		<tr>
			<td nowrap valign="bottom"><img src="/images/tabCornerLeft.gif" width="5" height="5" alt=""></td>
			<td nowrap width="25" style="border-bottom: solid 5px #E1E1E1;">&nbsp;</td>
			<td nowrap valign="bottom"><?=$img2?></td>
		<?
			foreach( $tabs as $key => $value ) {
		  		$sel = "";
		  		if($value["selected"]){
					if($key == 0)
						$img1 = "<img src=\"/images/tabBorderSelectedL.gif\" width=\"4\" height=\"33\" alt=\"\">";
					else
						$img1 = "<img src=\"/images/tabBorderSelectedInsideL.gif\" width=\"7\" height=\"33\" alt=\"\">";
		?>
			<td nowrap align="center" style="border-top: solid 1px #BCBCBC">
		<table cellspacing="0" cellpadding="0" border="0" style="margin-top: 3px">
		<tr>
			<td nowrap valign="bottom"><?=$img1?></td>
			<td nowrap style="background-color: #E1E1E1; padding-left: <?=$margin?>px; padding-right: <?=$margin?>px; padding-bottom: 8px;"><span style="color: #AD0000; font-size: 16px; font-weight: bold;">
			<?=$value["caption"]?></span>
			</td>
			<td nowrap valign="bottom"><img src="/images/tabBorderSelectedR.gif" width="4" height="33" alt=""></td>
		</tr>
		</table>
		</td>
			<td nowrap valign="bottom"><img src="/images/tabBorderOuterSelectedR.gif" width="4" height="36" alt=""></td>
			
		<?
				}
				else{
		?>
			
			<td nowrap align="center" style="border-top: solid 1px #BCBCBC; background-position: bottom; background-repeat: repeat-x; background-image: url(/images/tabBottomBg.gif); vertical-align: middle; padding-right: <?=$margin?>px; padding-left: <?=$margin?>px; padding-bottom: 8px;"><a href="<?=$value["path"]?>" class="a13pxBlue"><span style="font-size: 13px; font-weight: bold;"><?=$value["caption"]?></span></a></td>
			<td nowrap valign="bottom"><img src="/images/tabBorderR.gif" width="4" height="36" alt=""></td>
			
		<?
				}
			}
		?>
		
		<td nowrap width="100%" style="border-bottom: solid 5px #E1E1E1;">&nbsp;</td>
		<td nowrap valign="bottom"><img src="/images/tabCornerRight.gif" width="5" height="5" alt=""></td>
		</tr>
		</table>
		<?
	}
}
?>