<?php
$schema = "onecards";
require "start.php";
require_once( "$sPath/kernel/OneCardList.php" );

drawHeader("OneCards");

$objList = new OneCardList();
$objList->Update();
$objList->Draw();

drawFooter();

?>
