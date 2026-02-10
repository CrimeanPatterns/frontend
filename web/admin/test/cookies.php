<?php
error_reporting(E_ALL);
if(!empty($_GET)) {
	foreach ($_GET as $key => $value)
		setcookie($key, $value, time() + 3600 * 24 * 30);
	header('Location: ' . $_SERVER['SCRIPT_NAME'], true, 302);
	exit();
}

var_dump($_COOKIE);