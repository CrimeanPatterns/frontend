<?

class TRegionLinksFieldManager extends TCategoryLinksFieldManager{
	
	var $q = 0;
	var $qw;
	var $selectParents = false;
	
	function LoadPostData( &$arData ){
		$arSelectedOptions = array();		
		foreach($arData as $name=>$val){
			if(strstr($name, $this->FieldName)){
				$key = str_replace($this->FieldName,"",$name);
				$arSelectedOptions[$key] = $val == 'true'?'check':'nocheck';
			}
		}
		$this->SelectedOptions = $arSelectedOptions;
		$keys = array_keys($this->SelectedOptions);
		if(!empty($keys))
			$this->fullTreeNodes($keys);
	}
	
	function fullTreeNodes($arrKeys){
		$qRegion = new TQuery("SELECT {$this->CategoryExplorer->KeyField} FROM {$this->CategoryExplorer->Table}Content WHERE {$this->CategoryExplorer->ParentField} IN (".implode(',',$arrKeys).")");
		$arrKeys = array();
		while(!$qRegion->EOF){
			$is = isset($this->SelectedOptions[$qRegion->Fields[$this->CategoryExplorer->KeyField]]);
						
			if( !$is )
				$this->SelectedOptions[$qRegion->Fields[$this->CategoryExplorer->KeyField]] = 'lead';
			elseif( ($is && $this->SelectedOptions[$qRegion->Fields[$this->CategoryExplorer->KeyField]] == 'nocheck') )
				$this->SelectedOptions[$qRegion->Fields[$this->CategoryExplorer->KeyField]] = 'lead_nocheck';
				
			$arrKeys[] = $qRegion->Fields[$this->CategoryExplorer->KeyField];
			$qRegion->Next();
		}
		if(!empty($arrKeys))
			$this->fullTreeNodes($arrKeys);
	}
	
	function InputHTML($sFieldName = null, $arField = null){
		// hidden fields
		$s = $this->TreeHTML2( null, 'first' );
		return $s;	
	}
	
	function TreeHTML2( $nParentID, $level = null ){
		if( isset( $nParentID ) ){
			$sSQL = "SELECT {$this->CategoryExplorer->KeyField}, " . ( $this->CategoryExplorer->NameExpression ? $this->CategoryExplorer->NameExpression . " as " : "" ) . "{$this->CategoryExplorer->NameField} FROM {$this->CategoryExplorer->Table} {$this->CategoryExplorer->Joins} WHERE {$this->CategoryExplorer->KeyField} IN (SELECT Sub{$this->CategoryExplorer->KeyField} FROM {$this->CategoryExplorer->Table}Content WHERE {$this->CategoryExplorer->KeyField} = ".$nParentID." and Exclude = 0) ORDER BY {$this->CategoryExplorer->NameField}";
		}
		else
			$sSQL = "
			SELECT {$this->CategoryExplorer->KeyField}, " . ( $this->CategoryExplorer->NameExpression ? $this->CategoryExplorer->NameExpression . " as " : "" ) . "{$this->CategoryExplorer->NameField}
			FROM {$this->CategoryExplorer->Table} {$this->CategoryExplorer->Joins}
			WHERE {$this->CategoryExplorer->KeyField} IN (SELECT DISTINCT({$this->CategoryExplorer->KeyField}) FROM {$this->CategoryExplorer->Table}Content WHERE {$this->CategoryExplorer->KeyField} IS NOT NULL)  /*= 307*/
			  AND {$this->CategoryExplorer->KeyField} NOT IN (SELECT DISTINCT(Sub{$this->CategoryExplorer->KeyField}) FROM {$this->CategoryExplorer->Table}Content WHERE Sub{$this->CategoryExplorer->KeyField} IS NOT NULL)
			ORDER BY {$this->CategoryExplorer->NameField}";
			//$sSQL = "SELECT {$this->CategoryExplorer->KeyField}, {$this->CategoryExplorer->NameField} FROM {$this->CategoryExplorer->Table} WHERE {$this->CategoryExplorer->KeyField} NOT IN (SELECT {$this->CategoryExplorer->KeyField} FROM {$this->CategoryExplorer->Table}Relation) ORDER BY {$this->CategoryExplorer->NameField}";
		$q = new TQuery($sSQL);
		$s = '';
		$sArr = array();
		if( !$q->EOF ) {
			$i = 0;
			while( !$q->EOF ) {				
				$nValue = $q->Fields[$this->CategoryExplorer->KeyField];				
				$title = $q->Fields[$this->CategoryExplorer->NameField];

				$cq = new TQuery("SELECT count(*) as cnt FROM {$this->CategoryExplorer->Table}Content WHERE RegionID = $nValue and Exclude = 0");

                $childCount = $cq->Fields['cnt'];
				$sArr[$i] = array(
					'title' => $title . ($childCount ? " ({$childCount})" : ""),
					'key' => $this->FieldName.$nValue,
					'isLazy' => $childCount > 0,
				);
				$options = array();
				foreach($this->SelectedOptions as $key=>$opt){
					if($opt == 'check' || $opt == 'lead' || $opt == 'lead_nocheck')
						$options[] = $key;
				} 
				
				if( in_array( $nValue, $options ) ){
					$children = json_decode($this->TreeHTML2($nValue, 1));
					$sArr[$i]['children'] = $children;
					$sArr[$i]['select'] = ($this->SelectedOptions[$nValue] == 'lead')?false:true;
				}
				$q->Next();
				$i++;
			}
		}	
		$treeData = json_encode($sArr);
		if ($level == 'first'){ 
			$s .= '<div id="'.$this->CategoryExplorer->Table.'"></div>
			<div id="'.$this->CategoryExplorer->Table.'Inputs"></div>
			<script type="text/javascript">
				var tree'.$this->CategoryExplorer->Table.' = '.$treeData.';		
				$(function(){
			
				$("#'.$this->CategoryExplorer->Table.'").dynatree({
					checkbox: true,
					selectMode: 2,
					children: tree'.$this->CategoryExplorer->Table.',
					onSelect: function(select, node) {
						selectNodesByKey(node.data.key, select, "new");	
						console.log(node);
						'.($this->selectParents?'selectParents(node,select);':'').'		
						changePostData(node.data.key, select);		
					},
					onClick: function(node, event) {						
						if( node.getEventTargetType(event) == "title" )
							node.toggleSelect();
					},
					onKeydown: function(node, event) {
						if( event.which == 32 ) {
							node.toggleSelect();
							return false;
						}
					},
					onLazyRead: function(node){
						
		            	node.appendAjax({
		            	    url: "/manager/ajaxRequest/dealRegion.php",
				            data: {
				            	key: node.data.key,
				            	id: '.$this->Form->ID.'
							}
		                });
						
					},
					onPostInit: function(){
						var nodes = $("#'.$this->CategoryExplorer->Table.'").dynatree("getSelectedNodes");
						$("#'.$this->CategoryExplorer->Table.'Inputs").html("");
						$(nodes).each(function(val, node){
							if( $("#'.$this->CategoryExplorer->Table.'Inputs input[name="+node.data.key+"]").length == 0 )
							$("#'.$this->CategoryExplorer->Table.'Inputs").append("<input type=\'hidden\' name=\'"+node.data.key+"\' value=\'true\' />");
						});
					},
					
					cookieId: "tree'.$this->CategoryExplorer->Table.'",
					idPrefix: "tree'.$this->CategoryExplorer->Table.'"
				});
			
				$("#btnToggleSelect").click(function(){
					$("#tree2").dynatree("getRoot").visit(function(node){
						node.toggleSelect();
					});
					return false;
				});
				$("#btnDeselectAll").click(function(){
					$("#tree2").dynatree("getRoot").visit(function(node){
						node.select(false);
					});
					return false;
				});
				$("#btnSelectAll").click(function(){
					$("#tree2").dynatree("getRoot").visit(function(node){
						node.select(true);
					});
					return false;
				});
				<!-- Start_Exclude: This block is not part of the sample code -->
				$("#skinCombo")
				.val(0) // set state to prevent caching
				.change(function(){
					var href = "../src/"
						+ $(this).val()
						+ "/ui.dynatree.css"
						+ "?reload=" + new Date().getTime();
					$("#skinSheet").attr("href", href);
				});
				<!-- End_Exclude -->
			});
			
			//r = $("#'.$this->CategoryExplorer->Table.'").dynatree("getSelectedNodes");
			
			function selectParents(node, selected){
				node.select(selected);
				var parentNode = node.getParent();	
				node.getParent();			
				if(parentNode.data.key != "_1" && selected == true){
					selectParents(parentNode, selected);
				} 
			}
			
			function changePostData(key, select){
				var inp = $("input[name="+key+"]");				
				if(inp.length > 0)
					inp.val(select);
				else																
					$("#'.$this->CategoryExplorer->Table.'Inputs").append("<input type=\'hidden\' name=\'"+key+"\' value=\'"+select+"\' />");																	
			}	
			
			function selectNodesByKey(key, select, tree){
				
				if(tree == "new")
					tree = $("#'.$this->CategoryExplorer->Table.'").dynatree("getRoot").childList;									
				$(tree).each(function(val, elem){
					if(elem.data.key == key){
						elem.select(select);
					}
					if(elem.childList){						
						selectNodesByKey(key, select, elem.childList);
					}
				})								
			}
			
		
		</script>
		';	
		} else 
			return $treeData;
		return $s;
	}
	
	function SetFieldValue( $arValues )
	{
		global $Connection;
		$arSelectedOptions = array();
		$q = new TQuery( $this->GetSQL() );
		$opts = array();
		while( !$q->EOF )
		{
			$arSelectedOptions[$q->Fields[$this->CategoryExplorer->KeyField]] = 'check';
			$opts[] = $q->Fields[$this->CategoryExplorer->KeyField];
			$q->Next();
		}
		$this->SelectedOptions = $arSelectedOptions;
		$this->Field["Value"] = implode( ",", $opts );
		$keys = array_keys($this->SelectedOptions);
		if(!empty($keys))
			$this->fullTreeNodes($keys);
	}
	
	function Save()	{
		global $Connection;
		
		$currKeysArr = array();
		if(!empty($this->SelectedOptions)){
			$keys = implode(',',array_keys($this->SelectedOptions));
			$q = new TQuery("
				SELECT {$this->CategoryExplorer->KeyField}
				FROM {$this->TableName} 
				WHERE {$this->Form->KeyField} = {$this->Form->ID} 
				AND {$this->CategoryExplorer->KeyField} IN ($keys)");
			while(!$q->EOF){
				$currKeysArr[] = $q->Fields[$this->CategoryExplorer->KeyField];
				$q->Next();
			}
		}
		foreach($this->SelectedOptions as $value=>$flag){
			if(!in_array($value, $currKeysArr) && $flag == 'check'){
				$Connection->Execute( InsertSQL( $this->TableName, array( $this->Form->KeyField => $this->Form->ID, $this->CategoryExplorer->KeyField => $value ) ) );
			} elseif(in_array($value, $currKeysArr) && ($flag == 'nocheck' || $flag == 'lead_nocheck')) {
				$Connection->Execute( DeleteSQL( $this->TableName, array( $this->Form->KeyField => $this->Form->ID, $this->CategoryExplorer->KeyField => $value ), True ) );
			}
		}	
	}
}
