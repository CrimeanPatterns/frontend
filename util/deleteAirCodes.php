<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ALoginov
 * Date: 23.04.12
 * Time: 16:52
 * To change this template use File | Settings | File Templates.
 */
 
#!/usr/bin/php
require __DIR__."/../web/kernel/public.php";

echo "Start\n";
$sql = "SELECT AirCodeID, AirCode FROM AirCode WhERE AirCode NOT REGEXP '[a-zA-Z0-9]{3}'";
$q = new TQuery($sql);
$i = 0;
while(!$q->EOF){
    $Connection->Execute(DeleteSQL('AirCode', array('AirCodeID' => $q->Fields['AirCodeID'])));
    $q->Next();
    $i++;
}

echo "Deleted columns: $i\n";
