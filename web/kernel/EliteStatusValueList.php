<?

class EliteStatusValueList extends TBaseList{

    private $providers = [];
	
	function FormatFields($output = "html"){
		// phones
		$this->Query->Fields['Phones'] = TProviderPhoneSchema::getPhonesLink($this->Query->Fields['ProviderID'], $this->Query->Fields['EliteLevelID']);
		parent::FormatFields('');
		## Keyword List
		$this->Query->Fields['ValueText'] = implode(", ", SQLToSimpleArray("select ValueText
			from TextEliteLevel
			where EliteLevelID = {$this->Query->Fields['EliteLevelID']}", "ValueText"));
		## Link to the editing LP 
		$this->Query->Fields['ProviderID'] = "<a href='/manager/edit.php?ID={$this->OriginalFields['ProviderID']}&Schema=Provider'>{$this->Query->Fields['ProviderID']}</a>";

        if (!isset($this->providers[$this->OriginalFields['ProviderID']])) {
            $q = new TQuery("SELECT EliteLevelID, ProviderID, `Rank`, ByDefault FROM EliteLevel WHERE ProviderID = {$this->OriginalFields['ProviderID']}");
            foreach($q as $row) {
                $this->providers[$row['ProviderID']][] = $row;
            }
        }
        $id = $this->OriginalFields['EliteLevelID'];
        $rank = $this->OriginalFields['Rank'];
        $default = $this->OriginalFields['ByDefault'];
        $error = null;
        if ($default == "0") {
            $error = "Please select default elite level";
            foreach($this->providers[$this->OriginalFields['ProviderID']] as $row){
                if ($row['Rank'] == $rank && $row['EliteLevelID'] != $id && $row['ByDefault'] == 1) {
                    $error = null;
                    break;
                }
            }
        } else {
            $error = null;
            foreach($this->providers[$this->OriginalFields['ProviderID']] as $row){
                if ($row['Rank'] == $rank && $row['EliteLevelID'] != $id && $row['ByDefault'] == 1) {
                    $error = "Only one elite level may be selected by default";
                    break;
                }
            }
        }
        if ($error) {
            $this->Query->Fields['ByDefault'] = "<span style='color: red; font-weight: bolder' title='{$error}'>{$this->Query->Fields['ByDefault']}</span>";
        }

	}

}
