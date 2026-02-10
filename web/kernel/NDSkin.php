<?php

class NDSkin extends StandardSkin {

	private $footer;
	private $marker;
	private static $layout;

	public function drawHeader(){
		global $Interface, $sTitle;
		StickToMainDomain();
		$container = getSymfonyContainer();
        $request = $container->get('request_stack')->getMasterRequest(); // already contains request to aw_oldsite_bootfirewall, created in app/liteSymfony/app.php
		$kernel = $container->get('kernel');
		$this->marker = '%'.RandomStr(ord('A'), ord('Z'), 2);  // will use random marker to prevent XSS, using names like "%footer_scripts%"
		$request->attributes->set("marker", $this->marker);
		$request->attributes->set('title', $sTitle);
		if(!empty($Interface) && !empty($Interface->CssFiles))
			$cssFiles = implode("\n", array_map(function($file){
				if (strpos($file, '<!--') === 0)
					return $file;
				else
					return "<link rel=\"stylesheet\" type=\"text/css\" href=\"".htmlspecialchars($file)."\" />";
			}, $Interface->CssFiles));
		else
			$cssFiles = "";
		$request->attributes->set('cssFiles', $cssFiles);
		if(!empty(self::$layout))
			$request->attributes->set("layout", self::$layout);
        if (0 === strpos($request->getQueryString(), 'Provider=')) {
            $request->attributes->set('disallowCanonical', true);
        }
		$sessionActive = session_status() == PHP_SESSION_ACTIVE;
		$response = $kernel->handle($request, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
		if($sessionActive && session_status() != PHP_SESSION_ACTIVE) // restore session that was closed by symfony
			$container->get("session")->start();

        $container->get('request_stack')->push($request);
		$parts = explode($this->namedMarker('content'), $response->getContent());
		echo $parts[0];
		$this->footer = $parts[1];
	}

	/**
	 * set layout for NDSwitch page
	 * @param $layout - something like '@AwardWalletMain/Layout/onecard.html.twig'
	 */
	public static function setLayout($layout){
		self::$layout = $layout;
	}

	private function namedMarker($name){
		return $this->marker . $name;
	}

	public function drawFooter(){
		global $Interface, $cPage, $sBodyOnLoad;
		ob_start();
		?>
		<div id="fader" onclick="faderClick()">
		</div>
		<? if($cPage != "forum pages") { ?>
			<!-- browser ext params -->
			<input type="hidden" id="extCommand" value="init"/>
			<input type="hidden" id="extParams" value=""/>
			<input type="button" id="extButton" style="display: none;"/>
			<input type="button" id="extListenButton" style="display: none;" onclick="if(typeof(browserExt) == 'undefined') setTimeout(document.getElementById('extListenButton').onclick, 100); else browserExt.receiveCommand()"/>
			<input type="hidden" id="extBrowserKey" value="<?=htmlspecialchars(getBrowserKey())?>"/>
			<input type="hidden" id="extUserId" value="<? if(isset($_SESSION['UserID'])) echo $_SESSION['UserID'] ?>"/>

			<script type="text/javascript">
			var debugMode = <?=(ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG?"true":"false")?>;
			var dateFormat = '<?=javascriptDateFormat(DATE_FORMAT)?>';
			var thousandsSeparator = '<?=getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Localizer\LocalizeService::class)->getThousandsSeparator()?>';
			<? if(function_exists('ArrayVal')) { ?>
			var disableExtension = <?
				if(isset($_COOKIE['DBE']))
					echo '1';
				else
					echo '0';
			?>;
			<? } ?>
			</script>

			<div id="reviewPopup" style="display: none; width: 400px; height: 30px; overflow: hidden;"
				 class="popupWindow">
				<div id="reviewPopupText"></div>
			</div>

			<? $Interface->DrawBeginBox('id="termsPopup" style="height: 700px; width: 800px; position: absolute; z-index: 60; display: none;"', "Terms of Use"); ?>
			<div style="padding: 20px 0px 10px 0px;" id="termsText">
			</div>
			<? $Interface->DrawEndBox(); ?>

			<? $Interface->DrawBeginBox('id="framePopup" style="top: 0px; left: 0px;height: 100px; position: absolute; z-index: 50; width: 100px; display: none;"', "<span id='frameHeader'></span>"); ?>
			<div style="padding: 20px 0px 10px 0px;" id="popupFrameContainer">
			</div>
			<? $Interface->DrawEndBox();
			$Interface->DrawBeginBox('id="messagePopup" style="height: 200px; position: absolute; z-index: 50; width: 540px; display: none;"', "<div id='messageHeader'></div>"); ?>
				<div style='padding-top: 10px; margin: 5px 20px auto 20px;' id="messagePopupBody">
					<div id="messageText">Question</div>
					<div style="margin-top: 10px;" id="messageButtons">
					<?=$Interface->DrawButton("OK", "onclick='cancelPopup()' id=messageOKButton", 120)?>
					<?=$Interface->DrawButton("Cancel", "onclick='cancelPopup()' style='display: none;' id=messageCancelButton", 120)?>
					</div>
					<div class="clear"></div>
					<div id="messageProgress" class="progress" style="display: none;">
					Deleting..
					</div>
				</div>	<?
			$Interface->DrawEndBox();?>

			<?$Interface->DrawBeginBox('id="newAgentPopup" style="width: 550px;"', "Select connection type", true, "popupWindow"); ?>
				<div style="padding-top: 5px; margin: 5px 20px auto 20px;">
					<div class="question">You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.</div>
					<div class="buttons" id="newAgentButtons">
					<?=$Interface->DrawButton("Connect with another person", "onclick=\"location.href = '/agents/add-cconnection&BackTo=".urlencode($_SERVER['REQUEST_URI'])."'\"", 210)?>
					<?=$Interface->DrawButton("Just add a new name", "onclick=\"location.href = '/agent/add.php?BackTo=".urlencode($_SERVER['REQUEST_URI'])."'\"", 180)?>
					</div>
				</div> <?
			$Interface->DrawEndBox();

			$scriptFiles = '';
			if(!empty($Interface->ScriptFiles))
				foreach($Interface->ScriptFiles as $file)
					$scriptFiles .= ", '$file'";
			$Interface->FooterScripts[] = 'activateDatepickers("active");';
			if(!empty($sBodyOnLoad))
				$Interface->FooterScripts[] = $sBodyOnLoad . ';';
		}
		$footer = ob_get_clean();
		$content = str_replace($this->namedMarker('footer_scripts'), implode("\n", $Interface->FooterScripts), $this->footer);
		$content = str_replace($this->namedMarker('scripts_files'), $scriptFiles, $content);
		$content = str_replace($this->namedMarker('footer'), $footer, $content);
		echo $content;
	}

}
