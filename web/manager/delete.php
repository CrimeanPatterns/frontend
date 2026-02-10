<?php

require "start.php";

require "$sPath/kernel/TSchemaManager.php";

if (($_SERVER['REQUEST_METHOD'] != 'POST') || !isset($_POST['Schema'])) {
    exit("Invalid request");
}

$objSchemaManager = new TSchemaManager();
$objSchema = getSymfonyContainer()->get(\AwardWallet\Manager\SchemaFactory::class)->getSchema($_POST["Schema"]);
$arIDs = explode(",", $_POST["ID"]);
$arRows = [];
$bDelete = (ArrayVal($_POST, "Action") == "Delete" && isValidFormToken());

foreach ($arIDs as $nID) {
    if (strpos($nID, '.') > 0 && count($pair = explode('.', $nID)) == 2) {
        $table = $pair[0];
        $nID = $pair[1];
    } else {
        $table = $objSchema->TableName;
    }

    if (!schemaAccessAllowed($table) && !schemaAccessAllowed($table . ".Delete")) {
        $Interface->DiePage("Access denied to $table");
    }
    $arChilds = $objSchemaManager->DeleteRow($table, $nID, $bDelete);

    if (is_array($arChilds)) {
        $arRows = array_merge($arRows, $arChilds);
    }
}

if ($bDelete) {
    if (\method_exists($objSchema, 'AfterDelete')) {
        $objSchema->AfterDelete($arRows);
    }

    ScriptRedirect(urlPathAndQuery($_POST["BackTo"]));
} else {
    drawHeader("Delete record from " . $objSchema->Name);

    if (count($arRows) == 0) {
        echo "Records already deleted. <a href='{$_POST["BackTo"]}'>Go back</a>.<br>";
    } else {
        echo "<form method=post>";
        echo "<input type=hidden name=Action value=Delete>";
        echo "<input type=hidden name=Schema value=\"" . htmlspecialchars($_POST["Schema"]) . "\">\n";
        echo "<input type=hidden name=BackTo value=\"" . htmlspecialchars($_POST["BackTo"]) . "\">\n";
        echo "<input type='hidden' name='FormToken' value='" . GetFormToken() . "'>\n";
        echo "<input type=hidden name=ID value=\"" . htmlspecialchars($_POST["ID"]) . "\">\n";
        echo "<h2>You are about to delete following:</h2><br>";

        foreach ($arRows as $arRow) {
            echo "<b>{$arRow["Table"]} #{$arRow["ID"]}</b><br>";

            foreach ($arRow["Files"] as $arFile) {
                if ($arFile["Exist"]) {
                    echo "&nbsp;&nbsp;&nbsp;file {$arFile["File"]}<br>";
                }
            }
        }
        echo "<br><input class=button type=submit name=s1 value=Delete> ";
        echo "<input type=button class=button  name=c1 value=Cancel onclick=\"document.location.href='{$_POST["BackTo"]}'; return false;\">";
        echo "</form>";
    }
    drawFooter();
}
