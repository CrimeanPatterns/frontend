<?

class TBusinessPriceAdminList extends TBaseList{
	
	function GetEditLinks() {
		return "<a href=edit.php?ID=".$this->OriginalFields[$this->KeyField].$this->URLParamsString.">Edit</a>" .
		" | <input type=hidden name=sel".$this->OriginalFields[$this->KeyField]." value=\"\">\n<a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel".$this->OriginalFields[$this->KeyField].".value='1'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
	}

}
