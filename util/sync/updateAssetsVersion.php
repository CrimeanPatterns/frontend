<?php
error_reporting(E_ALL);

$config = __DIR__ . '/../../app/config/parameters.yml';
$js = __DIR__ . '/../../web/b/boot.js';

$crc = md5(file_get_contents($js));

$s = file_get_contents($config);
$s = preg_replace('#assets_version: \w+#ims', 'assets_version: ' . $crc, $s);
echo "assets version: " . $crc . "\n";

file_put_contents($config, $s);
