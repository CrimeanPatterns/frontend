<?
require( "../kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );

require( "$sPath/lib/admin/design/header.php" );

echo "<h1>Account exporter</h1>";

$form = new TBaseForm(array(
	"u" => array(
		"Type" => "string",
		"Required" => true,
		"Caption" => "Login",
	),
	"p" => array(
		"Type" => "string",
		"Required" => true,
		"Caption" => "Password",
		"InputType" => "password",
	),
	"type" => array(
		"Type" => "string",
		"Value" => "csv",
	)
));
$form->SubmitButtonCaption = "Export";
$form->Action = "/account/accountExporter.php";

echo $form->HTML();

require( "$sPath/lib/admin/design/footer.php" );
