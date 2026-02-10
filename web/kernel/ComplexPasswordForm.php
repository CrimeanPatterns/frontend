<?
class ComplexPasswordForm extends TForm  {

	public function CheckScriptsBody(){
		$result = parent::CheckScriptsBody();

		$result .= "
		var errors = passwordComplexity.getErrors();
		if(errors.length)
			{ Form.Pass.focus(); return false; };
		";

		return $result;
	}

}
