<?php
$schema = "UserAdmin";
require "start.php";

$userId = intval(ArrayVal($_GET, 'UserID'));

drawHeader("One time codes for user $userId");

$q = new TQuery("select * from OneTimeCode where UserID = $userId order by CreationDate desc");
if($q->EOF)
    echo "no codes";
else{
    echo "<table border=1 class=mainTable>";
    echo "<tr><td><b>Code</b></td><td><b>Date</b></td></tr>";
    foreach ($q as $row)
        echo "<tr><td>{$row['Code']}</td><td>{$row['CreationDate']}</td></tr>";
    echo "</table>";
}


drawFooter();
