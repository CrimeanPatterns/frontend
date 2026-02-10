<?

class TCategoryExplorer extends TBaseCategoryExplorer{

	// call this function after setting all clss properties
	function Init()
	{
		global $Connection, $QS;
        // prepare
		if( !isset( $this->Table ) )
			DieTrace("Please Set Table");
		if( !isset( $this->ParentField ) )
			DieTrace("Please Set ParentField");
		if( !isset( $this->KeyField ) )
			$this->KeyField = $Connection->PrimaryKeyField($this->Table);
        //initiate category array with top-most parent nodes
		$sql = "
			SELECT {$this->KeyField}, " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . " {$this->NameField}
			FROM {$this->Table} 
			{$this->Joins}
			WHERE {$this->KeyField}  IN (SELECT DISTINCT({$this->KeyField}) FROM {$this->Table}Content) 
			/* AND {$this->KeyField} NOT IN (SELECT DISTINCT(Sub{$this->KeyField}) FROM {$this->Table}Content) */
			ORDER BY {$this->NameField}
		";
		$this->Tree = array(1 => SQLToArray($sql, $this->KeyField, $this->NameField));
        //Loop through the top-most parent nodes and start exploring their sub-nodes
		$objRS = New TQuery($sql);
		while(!$objRS->EOF)
		{
			$nCategoryID = $objRS->Fields[$this->KeyField];
			$sName = $objRS->Fields[$this->NameField];
            //begin populating the left menu array
			$this->leftMenu = $this->leftMenu + array(
				$sName => array(
					"caption"	=> $sName,
					"path"		=> $this->targetURL."?Cat1=$nCategoryID",
					"selected"	=> $nCategoryID == intval( ArrayVal($QS, "Cat1" ) ),
					"id"		=> $nCategoryID,
				)
			);
            //end populating the left menu array
            #begin populating allIds array
			if(!in_array($nCategoryID, $this->allIds))
				$this->allIds[] = $nCategoryID;
#begin populatin
//			if($this->leftMenu[$sName]["selected"])
//				$this->buildCategoryPath(1, $this->leftMenu[$sName]["path"], $this->leftMenu[$sName]["caption"]);
//			$this->Explore($nCategoryID, $this->leftMenu[$sName]["subMenu"], $this->leftMenu[$sName]["path"], $this->leftMenu[$sName]["selected"], 1);
			$objRS->Next();
		}

        // begin getting selected categories
		$bCatSelected = True;
		foreach( $this->Tree as $nLevel => &$arCategories )
		{
			// sort categories alphab
			asort( $arCategories );
			$nSelected = intval( ArrayVal($QS, "Cat$nLevel" ) );
			// add category to first level for one-category filtering
			if( ( $nLevel == 1 ) && !isset( $arCategories[$nSelected] ) )
			{
				$q = new TQuery("select Name from {$this->Table} where {$this->KeyField} = $nSelected");
				if( !$q->EOF )
					$arCategories[$nSelected] = $q->Fields["Name"];
			}
			if( isset( $arCategories[$nSelected] ) && $bCatSelected )
			{
				if( $nLevel > 1 )
				{
					// check that selected category is child of parent
					$q = new TQuery( "select 1 from {$this->Table}Content where Sub{$this->ParentField} = $nSelected and {$this->KeyField} = " . $this->Selected[$nLevel - 1] );
					if( $q->EOF )
						$bCatSelected = False;
				}
				if( $bCatSelected )
					$this->Selected[$nLevel] = $nSelected;
			}
			else
				$bCatSelected = False;
		}
		if(isset($QS["ID"])){
			$this->privCurCatId = intval($QS["ID"]);
            $q = new TQuery("SELECT " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField} FROM {$this->Table} {$this->Joins} WHERE {$this->KeyField} = {$this->privCurCatId}");
			$this->privCurCatName = $q->Fields[$this->NameField] ?? null;
		}
        // end getting selected categories
		$this->completeCategories();
	}
		
	function Explore($nID, &$subMenu, $linkPath, $parentSelect, $nLevel)
	{
	global $QS;
    #		if(isset($this->Tree[$nLevel][$nID]))
    #			return false;
    //begin calculating the depth of the tree
		if($this->depth<$nLevel)
			$this->depth = $nLevel;
    //end calculating the depth of the tree
		$nLevel++;
		$sql = "
			SELECT {$this->KeyField}, " . ( $this->NameExpression ? $this->NameExpression . " as " : "" ) . "{$this->NameField}
			FROM {$this->Table}
			{$this->Joins}
			WHERE {$this->KeyField} IN (SELECT {$this->KeyField} FROM {$this->Table}Content WHERE Sub{$this->KeyField} = $nID ORDER BY {$this->NameField})
			ORDER BY Name
		";
		$objRS = New TQuery($sql);
		while( !$objRS->EOF )
		{
			$nCategoryID = $objRS->Fields[$this->KeyField];
			$sName = $objRS->Fields[$this->NameField];
//begin populating the left menu array
			if(!is_array($subMenu))
				$subMenu = array();
#if(isset($categoriesTAr[$level][$nCategoryID])){
			$subMenu = $subMenu + array(
				$sName => array(
					"caption"	=> $sName,
					"path"		=> $linkPath . "&Cat".$nLevel."=$nCategoryID",
					"selected"	=> $nCategoryID == intval( ArrayVal($QS, "Cat{$nLevel}" ) ) && $parentSelect,
					"id"		=> $nCategoryID,
				)
			);
//end populating the left menu array
#begin populating allIds array
			if(!in_array($nCategoryID, $this->allIds))
				$this->allIds[] = $nCategoryID;
#begin populating allIds array
			if($subMenu[$sName]["selected"])
				$this->buildCategoryPath($nLevel, $subMenu[$sName]["path"], $subMenu[$sName]["caption"]);
			if(!isset($this->Tree[$nLevel][$nCategoryID]))
			{
				$this->Tree[$nLevel][$nCategoryID] = $sName;
			}
			$this->Explore($nCategoryID, $subMenu[$sName]["subMenu"], $subMenu[$sName]["path"], $subMenu[$sName]["selected"], $nLevel);
			$objRS->Next();
		}
	}
	
}
