#!/usr/bin/php
<?
require __DIR__.'/../../web/kernel/public.php';

echo "cAP = checkAndroidPayments\n";
const GS_REV = '13895263954620308086';

$date = date("Ym", time());
$gsPath = 'gs://pubsite_prod_rev_'.GS_REV.'/sales/salesreport_'.$date.'.zip';
echo "cAP: Preparing to download $gsPath\n";
exec("gsutil cp $gsPath util/check/dls/salesreport_$date.zip");
echo "cAP: download complete, unzipping\n";
exec("unzip -o util/check/dls/salesreport_$date.zip -d util/check/dls/");

$row = 1;
if (($handle = fopen("util/check/dls/salesreport_$date.csv", "r")) !== FALSE) {
    $csvData = array();
    $i = 0 ;
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        if ($i == 0)
            $csvCaptions = $data;
        else
            $csvData[] = $data;
        $i++;
    }
    fclose($handle);
}else
    die("cAP: ERROR: csv report not found!");
// print_r($csvCaptions);
echo "\ncAP: Parsing ".count($csvData)." rows of data\n";
for ($i = 0; $i < count($csvData); $i++){
    $oid = $csvData[$i][0];
    $q = new TQuery("select * from Cart where BillingTransactionID = '12999763169054705758.".$oid."'");
    if ($q->EOF){
        echo "$oid - record not found.";
        $ot = $csvData[$i][2];
        $otm = date("Y-m-d H:i:s", $ot);
        echo " Date: $otm, ID: $oid";
        $q = new TQuery("select InAppPurchase.*, Usr.FirstName, Usr.LastName
            from InAppPurchase join Usr on InAppPurchase.UserID = Usr.UserID
            where EndDate is null and abs(timediff(DATE_ADD(StartDate, INTERVAL 12 hour), '$otm')) <= 1000");
        if ($q->EOF)
            echo ", No open records found within 10 minutes.";
        else{
            echo ", Open records found within 10 minutes: ";
            while (!$q->EOF){
                echo " | InAppPurchaseID: ".$q->Fields['InAppPurchaseID'].", FirstName: ".$q->Fields['FirstName'].", LastName: ".$q->Fields['LastName'];
                $q->Next();
                }
            }
        echo "\n====================\n";
    }
    else{
        if (getopt("f"))
            echo "$oid - record found.\n====================\n";
    }
}
?>
