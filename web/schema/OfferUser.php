<?

// #5857

class TOfferUserSchema extends TBaseSchema
{
	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "OfferUser";
        $this->ListClass = "OfferUserList";
        $q = new TQuery("SELECT OfferID, Code FROM Offer ORDER BY OfferID DESC");
        foreach ($q as $row) {
        	$OfferIDOptions[$row["OfferID"]] = $row["OfferID"]." (".$row["Code"].")";
		}
		$this->Fields = array(
			"OfferUserID" => array(
            	"Caption" => "OfferUserID",
                "Type" => "integer",
                "InputAttributes" => "readonly",
                ),
			"OfferID" => array(
            	"Caption" => "OfferID",
                "Type" => "integer",
                "Options" => $OfferIDOptions,
                ),
            "UserID" => array(
            	"Caption" => "UserID",
                "Type" => "integer",
                "FilterField" => "Usr.UserID",
                ),
            "Login" => array(
            	"Type" => "string",
            	"FilterField" => "Usr.Login", 
            	),
            "FirstName" => array(
            	"Type" => "string",
                "FilterField" => "Usr.FirstName", 
                ),
			"LastName" => array(
				"Type" => "string",
				"FilterField" => "Usr.LastName", 
            	),
            "Params" => array(
                "Type" => "string",
                "InputType" => "textarea",
		"Size" => 4000,
                "InputAttributes" => "style='width: 300px;'",
)
        	);
        }
        
	function GetFormFields(){
		$fields = parent::GetFormFields();
                $offerUserId = isset($_GET['ID']) ? $_GET['ID'] : 0;
        	foreach (array('OfferUserID', 'Login', 'FirstName', 'LastName') as $f){
            	unset($fields[$f]);
            }
                ArrayInsert($fields, "Params", true, array("Preview" => array(
			"Type" => "string",
			"InputType" => "html",
			"Database" => false,
			"HTML" => "<input type='button' value='Show Preview' onclick='showPreview(); return false;'>
			<script>
			function showPreview(){
				var form = document.forms['editor_form'];
                form.action = 'http://{$_SERVER['HTTP_HOST']}/manager/offer/preview/".$offerUserId."?preview=yes';
				form.target = '_blank';
				form.submit();
				setTimeout(function(){
					form.action = '';
					form.target = '';
					submitonce(form, true);
				}, 1000);
			}
			</script>",
		)));
		//?OfferUserID=".$offerUserId."';
		return $fields;
	}
        
	function TuneList( &$list ){
		parent::TuneList( $list );
        $list->SQL = "SELECT OfferUser.*, Usr.Login, Usr.FirstName, Usr.LastName FROM OfferUser JOIN Usr ON OfferUser.UserID = Usr.UserID";
    } 

    function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
        if ((ArrayVal($_GET, 'ID', 0) == 0) && (isset($_GET["OfferID"]))){
        	$form->Fields["OfferID"]["Value"] = intval($_GET["OfferID"]);
            $form->SQLParams["CreationDate"] = "now()";
        }
        $form->OnCheck = array($this, "CheckForm", $form);
    }
    
    function CheckForm( $form ){
        // check User Existance, check offer existance, check record in OfferUser
//        $q = new TQuery("SELECT count(*) as N FROM OfferUser WHERE OfferID = ".$form->Fields["OfferID"]["Value"]." AND UserID = ".$form->Fields["UserID"]["Value"]);
//        if ($q->Fields['N']){
//            return $form->Fields['UserID']['Error'] = "This offer has been already applied to the user №".$form->Fields["UserID"]["Value"];
//        }
		$form->Uniques = array(
        	array(
            "Fields" => array("OfferID", "UserID"),
            "ErrorMessage" => "This offer has already been applied to the user to the user №".$form->Fields["UserID"]["Value"] 
            ),
        );
        $q = new TQuery("SELECT count(*) as N FROM Usr WHERE UserID = ".$form->Fields["UserID"]["Value"]);
        if (!$q->Fields['N']){
            return $form->Fields['UserID']['Error'] = "User №".$form->Fields["UserID"]["Value"]." dosen't exist";
        }
        return null;
    }
}
?>
