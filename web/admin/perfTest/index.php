<?
require "../../kernel/public.php";

$sTitle = "Performance test results";

require "$sPath/lib/admin/design/header.php";

foreach(glob("$sPath/admin/perfTest/logs/*.html") as $file){
	$baseName = basename($file);
	echo "<a href='logs/$baseName'>$baseName</a><br/>";
}

require "$sPath/lib/admin/design/footer.php";
