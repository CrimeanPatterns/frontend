<?
require_once __DIR__."/../kernel/public.php";

require( "start.php" );
require_once "$sPath/kernel/TForm.php";

drawHeader("Booking API test");

global $Interface;

$fields = array(
	"partner" => array(
		"Type" => "string",
		"Caption" => "Partner",
		"Note" => "enter partner id",
		"Required" => true,
		"Value" => 'pointimize',
	),
    "json" => array(
        "Type" => "string",
        "Caption" => "JSON",
        "Note" => "JSON",
        "Required" => true,
        "InputType" => "textarea",
        "InputAttributes" => "style='width: 600px; height: 400px;'",
        "Value" => '',
    ),
);

$form = new TForm($fields);
$form->SubmitButtonCaption = "Submit";
$form->SubmitURL = '/awardBooking/add?ref=147#pointimize';

if ($form->IsPost) {
//    $partner = $form->Fields['Goto']['Value'];
//    $fields = $form->Fields['UserID']['Row'];
    var_dump($form->Fields['partner']);
    var_dump($form->Fields['json']);
    die();
}

echo $form->HTML();

echo "<script type='text/javascript'>
</script>";

drawFooter();

?>
