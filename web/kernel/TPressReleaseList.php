<?php

class TPressReleaseList extends TBaseList{
	public function GetEditLinks(){
		$arFields = &$this->Query->Fields;
		return "<a href='/pr/{$arFields['ForumID']}'>View</a> | " . parent::GetEditLinks();
	}
}