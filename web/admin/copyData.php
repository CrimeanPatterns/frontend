<?
require( "../kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once( "$sPath/kernel/TSchemaManager.php" );

require( "$sPath/lib/admin/design/header.php" );

echo "<h1>Copy table rows with all dependencies</h1>";

$form = new TBaseForm(array(
	"Rows" => array(
		"Type" => "string",
		"Required" => true,
		"InputType" => "textarea",
		"Note" => "Enter one or more staring rows, in format 'Table,ID'"
	),
	"ExcludeTables" => array(
		"Type" => "string",
		"Required" => true,
		"InputType" => "textarea",
		"Value" => implode("\n", ['Session', 'QsTransaction', 'OA2Token', 'OA2Code', 'PasswordVaultUser', 'PasswordVault', 'PasswordVaultLog', 'Coupon', 'MobileDevice']),
		"Note" => "Enter one or more excluded rows, in format 'Table'"
	),
	"Host" => array(
		"Type" => "string",
		"Required" => true,
	),
	"Port" => array(
		"Type" => "integer",
		"Required" => true,
        "Value" => 3306,
	),
    "Login" => array(
   		"Type" => "string",
   		"Required" => true,
   	),
    "Password" => array(
   		"Type" => "string",
   		"InputType" => "password",
   		"Required" => true,
   	),
	"Database" => array(
		"Type" => "string",
		"Required" => true,
	),
	"Apply" => array(
		"Type" => "boolean",
		"Required" => true,
		"Value" => 0,
	),
	"CheckExistence" => array(
		"Type" => "boolean",
		"Required" => true,
		"Value" => 1,
	),
	"InsertIgnore" => array(
		"Type" => "boolean",
		"Required" => true,
		"Value" => 0,
	),
));
$form->SubmitButtonCaption = "Copy";

if($form->IsPost && $form->Check()){
	ob_end_flush();
	$error = copyData(
	    $form->Fields["Rows"]["Value"],
	    array_map("trim", explode("\n", $form->Fields["ExcludeTables"]["Value"])),
        $form->Fields["Host"]["Value"],
        $form->Fields["Port"]["Value"],
        $form->Fields["Login"]["Value"],
        $form->Fields["Password"]["Value"],
        $form->Fields["Database"]["Value"],
        $form->Fields["Apply"]["Value"] == "1",
        $form->Fields["CheckExistence"]["Value"] == "1",
        $form->Fields["InsertIgnore"]["Value"] == "1"
    );
	if(!empty($error))
		$Interface->DrawMessage($error, "error");
}

echo $form->HTML();

require( "$sPath/lib/admin/design/footer.php" );

function copyData($lines, $excludeTables, $host, $port, $login, $password, $database, $apply, $checkExistence, $insertIgnore) {
	global $Connection;

	$backup = new \PdoConnection();
	$backup->Open(["Host" => $host, "Port" => $port, "Database" => $database, "Login" => $login, "Password" => $password], true);

	$schemaManager = new TSchemaManager($backup);

	$loadChild = function($table, $id) use($schemaManager){
		$primaryKey = $schemaManager->Tables[$table]['PrimaryKey'];
		$q = new TQuery("select 1 from $table where {$primaryKey} = $id");
		return $q->EOF;
	};

	$rows = [];
	$excludeRows = [];

	foreach(explode("\n", $lines) as $line){
		$parts = explode(",", trim($line));
		if(count($parts) != 2)
			return "Error in table spec, should be 2 parts: $line";
		if(!preg_match('#^\w+$#ims', $parts[0]))
			return "Error in table spec, first part should be table name: $line";
		if(!is_numeric($parts[1]))
			return "Error in table spec, second part should be row id: $line";

		$q = new TQuery("select * from {$parts[0]} where ".$schemaManager->Tables[$parts[0]]["PrimaryKey"]." = {$parts[1]}", $backup);
		if($q->EOF)
			return "Row with id {$parts[1]} not found in table {$parts[0]}";
		if($checkExistence && !$loadChild($parts[0], $parts[1]))
			return "Row with id {$parts[1]} already exists in table {$parts[0]} in this database";
		if(in_array("{$parts[0]}_{$parts[1]}", $excludeRows))
			return "Row with id {$parts[1]} from table {$parts[0]} already excluded";

		$rows = array_merge($rows, $schemaManager->ChildRows($parts[0], $q->Fields, $excludeRows, function(string $table, $id) use ($excludeTables) {
            return !in_array($table, $excludeTables);
        }));
		$rows[] = $schemaManager->SingleRow($parts[0], $q->Fields);
	}

	$rows = array_reverse($rows);
	echo "<table border=1>";
	$stats = [];
	foreach($rows as $row) {
		echo "<tr><td>{$row['Table']}</td><td>{$row['ID']}</td></tr>";
		if(empty($stats[$row['Table']]))
			$stats[$row['Table']] = 1;
		else
			$stats[$row['Table']]++;
	}
	echo "</table>";

	echo "stats: <pre>" . var_export($stats, true) . "</pre>";

	if($apply) {
        echo "<div>Applying changes</div>";
        $Connection->Execute("start transaction");
    }
	else {
	    echo "<div>Test mode</div>";
    }

    foreach($rows as $row){
        $q = new TQuery("select * from {$row['Table']} where ".$Connection->PrimaryKeyField($row['Table'])." = '".addslashes($row['ID'])."'", $backup);
        if($q->EOF)
            DieTrace("Row not found: {$row['Table']}, {$row['ID']}");
        foreach($q->Fields as $key => &$value){
            if($value == '' && $schemaManager->Tables[$row['Table']]['Fields'][$key]['Null'])
                $value = "null";
            else
                $value = "'".addslashes($value)."'";
        }
        $sql = InsertSQL($row['Table'], $q->Fields);
        if ($insertIgnore) {
            $sql = preg_replace('#^insert #ims', 'insert ignore ', $sql);
        }
        echo $sql.";<br/>";
        if ($apply) {
            $Connection->Execute($sql);
        }
    }

    if ($apply) {
        $Connection->Execute("commit");
    }
    echo "<div><b>success</b></div>";

	return null;
}

