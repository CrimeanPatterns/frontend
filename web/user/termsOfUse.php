<?
require( "../kernel/public.php" );
?>
<!--<div style='padding-right: 10px; max-height: 400px; overflow: auto;'>-->
<?
$objRS = New TQuery( "SELECT BodyText FROM Forum WHERE ForumID = 9", $Connection );
print $objRS->Fields["BodyText"];
?>
<!--</div>-->
