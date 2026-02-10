<?
require( "../../kernel/public.php" );
$bSecuredPage = False;
$sTitle = "Status variations";
require( "$sPath/lib/admin/design/header.php" );
?>
<table cellpadding="5" cellspacing="0" class="detailsTable">
	<tr>
		<td>Loyalty Program</td>
		<td>Status Values</td>
	</tr>
<?
$providersRS = new TQuery("SELECT * FROM Provider WHERE State = 1");
while(!$providersRS->EOF){
	echo "<tr><td><strong>" . $providersRS->Fields["DisplayName"] . "</td><td>&nbsp;</td></tr>";
	$statusPropertyRS = new TQuery("SELECT * FROM ProviderProperty WHERE Kind = 3 AND ProviderID = " . $providersRS->Fields["ProviderID"] );
	if($statusPropertyRS->Fields["ProviderPropertyID"] != ""){
		$uniqueValuesRs = new TQuery("SELECT DISTINCT Val FROM AccountProperty WHERE ProviderPropertyID = " . $statusPropertyRS->Fields["ProviderPropertyID"]);
		while(!$uniqueValuesRs->EOF){
			echo "<tr><td>&nbsp;</td><td>" . $uniqueValuesRs->Fields["Val"] . "</td></tr>";
			$uniqueValuesRs->Next();
		}
	}
	$providersRS->Next();
}
?>
</table>
<?
require "$sPath/lib/admin/design/footer.php";
?>