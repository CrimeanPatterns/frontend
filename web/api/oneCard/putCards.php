<?php
error_reporting(E_ALL);

const PRINTER_PASSWORD = 'some_printer_password';
const DATABASE_DSN = 'mysql:dbname=awardwallet;host=127.0.0.1;port=33063';
const DATABASE_USER = 'awardwallet';
const DATABASE_PASSWORD = 'awardwallet';

if (!isset($_POST['Password']) || ($_POST['Password'] !== PRINTER_PASSWORD)) {
    die("Access Denied");
}

if (!isset($_POST['Rows'])) {
    die("Missing Rows");
}

$rows = json_decode($_POST['Rows'], true);
if (!is_array($rows)) {
    die("Invalid data");
}

$connection = new \PDO(
    DATABASE_DSN,
    DATABASE_USER,
    DATABASE_PASSWORD,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$connection->exec("delete from OneCardPrinting");

foreach ($rows as $row) {
	foreach ($row as $key => &$value) {
        $value = $connection->quote($value);
    }
	$sql = "insert into OneCardPrinting(" . implode(", ", array_keys($row)) . ") values(" . implode(", ", $row) . ")";
	$connection->exec($sql);
}

echo "OK";
