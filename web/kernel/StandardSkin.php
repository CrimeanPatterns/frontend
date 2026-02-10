<?

class StandardSkin{

	public function init(){

	}

	public function drawCss(){
		
	}

	public function drawScripts(){

	}

	public function restructureMenus(){
		global $singleUser, $leftMenu, $othersMenu, $manageMenu, $connMenu, $arSwitchText, $cPage, $topMenu, $Interface;
		if($cPage == "forum pages"){
			if(!isset($_GET['f']) || ($_GET['f'] != '16'))
				$topMenu["Forum"]["selected"] = true;
			requirePasswordAccess();
		}
		foreach($topMenu as $key => $value)
			if($Interface->comparePaths($value["path"]))
				$topMenu[$key]["selected"] = true;
		// restructure menus
		$singleUser = true;
		if(isset($leftMenu) && isset($leftMenu["My Award Programs"]) && isset($othersMenu) && is_array($othersMenu) && (count($othersMenu) > 0)){
			$manageMenu = $leftMenu;
			unset($manageMenu["My Award Programs"]);
			unset($manageMenu["All Award Programs"]);
			$connMenu = $manageMenu['My Connections'];
			unset($leftMenu['My Connections']);
			unset($manageMenu['My Connections']);
			foreach(array_keys($manageMenu) as $key)
				unset($leftMenu[$key]);
			$leftMenu = array_merge($leftMenu, $othersMenu);
			if(SITE_MODE == SITE_MODE_BUSINESS)
				$leftMenu["My Award Programs"]["caption"] = "{$_SESSION['UserFields']['Company']}";
			else
				$leftMenu["My Award Programs"]["caption"] = "{$_SESSION['FirstName']} {$_SESSION['LastName']}";
			if(isset($leftMenu["Add New Person"]))
				ArrayInsert($leftMenu, "Add New Person", false, array("My Connections" => $connMenu));
			else
				$leftMenu["My Connections"] = $connMenu;
			$othersMenu = $manageMenu;
			$singleUser = false;
		}
		$arSwitchText = array(
			SITE_MODE_PERSONAL => 'BUSINESS INTERFACE',
			SITE_MODE_BUSINESS => 'PERSONAL INTERFACE'
		);
	}

	public function drawHeader(){
		$this->checkDomain();
		require( __DIR__ . "/../design/regularHeader.php" );
	}

	public function drawFooter(){
		require( __DIR__ . "/../design/regularFooter.php" );
	}

	public function showIndexPage(){
		$this->checkDomain();
	}

	/* ensure that we are not running full version inside iframe */
	public function checkDomain(){
		if(stripos($_SERVER['HTTP_HOST'], 'iframe.') === 0)
			Redirect("/api/iframe/loader.php?Reload=1");
	}

}
