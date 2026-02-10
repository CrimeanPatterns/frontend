<?php

require "../kernel/public.php";
$bSecuredPage = false;
$sTitle = "Web Service to Track Loyalty Program Balances";
$metaDescription = "We provide WSDL web siervice to track loyalty program balances and expirations";

if (NDInterface::enabled()) {
    Redirect(getSymfonyContainer()->get("router")->generate("aw_page_index", ["page" => "partners"]));
}

// begin determining menus
require $sPath . "/design/topMenu/main.php";

require $sPath . "/design/header.php";
// begin actual content
$objRS = new TQuery("SELECT Title, BodyText FROM Forum WHERE ForumID = 12 AND Visible = TRUE");

if (!$objRS->EOF) {
    echo $objRS->Fields["BodyText"];
}

// end actual content
require $sPath . "/design/footer.php";
