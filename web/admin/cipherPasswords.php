<?php
require "../kernel/public.php";
require_once "../kernel/TForm.php";

$objForm = new TForm(
	array(	
		"Confirm" => array(
			"Type" => "string",
			"Note" => "yes?",
			"Required" => true,
		),
	)
);
$objForm->SubmitButtonCaption = "Encode";
$sTitle = "Cipher passwords";

require "$sPath/lib/admin/design/header.php";

if( $objForm->IsPost && $objForm->Check() )
	if( $objForm->Fields["Confirm"]["Value"] == "yes" ){
		CipherPasswords();
	}
	else 
		$objForm->Error = "Confirmatiion required";
		
echo $objForm->HTML();

function CipherPasswords(){
	global $cert, $publicKey, $Connection;
	echo "encoding passwords..<br>";
	$q = new TQuery("select * from Account where Pass <> ''");
	while( !$q->EOF ){
		if( strlen( $q->Fields["Pass"] ) != CRYPTED_PASSWORD_LENGTH ){
			$sCrypted = CryptPassword( $q->Fields["Pass"] );
			$sDecrypted = DecryptPassword( $sCrypted );
			echo $q->Fields["AccountID"] . "<br>";
			if( $sDecrypted != $q->Fields["Pass"] )
				DieTrace("Failed to crypt");
			$Connection->Execute("update Account set Pass = '".addslashes( $sCrypted )."' where AccountID = {$q->Fields['AccountID']}");
		}
		$q->Next();
	}
	echo "checked {$q->Position} passwords<br><br>";
}

/*function CreateCert(){
	$dn = array(
	    "countryName" => "United States",
	    "stateOrProvinceName" => "Ohio",
	    "localityName" => "Columbus",
	    "organizationName" => "Itlogy",
	    "organizationalUnitName" => "Dev Team",
	    "commonName" => "Vlaidimir",
	    "emailAddress" => "vsilantyev@itlogy.com"
	);	
	$privkey = openssl_pkey_new( array( 
		"private_key_bits" => 512,
	) );
	$csr = openssl_csr_new($dn, $privkey);
	openssl_csr_export_to_file( $csr, "$sPath/admin/passkey.pem" );
}*/

require "$sPath/lib/admin/design/footer.php";
?>