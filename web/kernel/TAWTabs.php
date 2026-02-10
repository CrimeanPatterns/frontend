<?

class TAWTabs extends TBaseTabs{

	public $id = "tabs";

	function drawTabs2(){
		global $lastRowKind, $Interface;
		$this->selectATab();
		if(isGranted("SITE_ND_SWITCH"))
			return $this->ndDrawTabs();
		echo '<div id="'.$this->id.'" class="tabs">';
		$lastClass = "first";
		foreach( $this->Fields as $key => $value ){
		  	if($value["selected"])
				$class = "active";
			else
				$class = ArrayVal($value, "class", "normal");
		  	echo "<div class=\"{$lastClass}To".ucfirst($class)."\"><div class=\"tabIcon\"></div></div>";
			echo "<a id=\"tab_{$this->id}_{$key}\" href=\"".htmlspecialchars($value['path'])."\"";
			if(isset($value['onclick']))
				echo " onclick=\"".htmlspecialchars($value['onclick'])."\"";
			echo " class=\"".htmlspecialchars($class)."\"><div class=\"".htmlspecialchars($class)."\"><div class=\"caption\">";
            if (isset($value['image']))
                echo "<img src =\"".htmlspecialchars($value['image']).'" style = "margin-right: 10px">';
            echo "<span>{$value['caption']}</span></div><div class=\"tabIcon\"></div></div></a>";
			if($lastClass == "first")
				$lastRowKind = ucfirst($class);
			$lastClass = $class;
		}
		echo "<div id=\"div_{$this->id}_last\" class=\"".htmlspecialchars($lastClass)."ToLast\"></div>";
		echo '</div>';
		$Interface->FooterScripts[] = "$(document).ready(resizeTabs);";
	}

	function ndDrawTabs(){
		echo "<ul class='tabs-navigation'>";
		foreach( $this->Fields as $key => $value ){
			echo "<li><a id='tab_{$this->id}_{$key}'";
			if($value['selected'])
				echo " class='active'";
			echo " href=\"".htmlspecialchars($value['path'])."\"";
			if(isset($value['onclick']))
				echo " onclick=\"".htmlspecialchars($value['onclick'])."\"";
			echo ">";
			if (isset($value['image']))
       			echo "<img src =\"".htmlspecialchars($value['image']).'" style = "margin-right: 10px">';
			echo $value['caption'] . "</a></li>";
		}
		echo "</ul><div class='tabs-line'></div>";
	}

}
