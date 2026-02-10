<?php

$tripsCodes = isset($_GET['code']) && is_string($_GET['code']) ? $_GET['code'] : '';
if (isset($_GET['size']) && is_string($_GET['size']) && preg_match('/^(\d+)(?:x(\d+))?$/ims', $_GET['size'], $mathes)) {
    $size = $_GET['size'];
} else {
    $size = '44x44';
}

header('Content-Type: image/gif');
header("Cache-control: public");
header("Cache-control: s-maxage=0, max-age=3600");

$pm = "PM=b%3Aring10%3Ablack%2B\"%25U\"18";
readfile("http://www.gcmap.com/map?p=" . urlencode($tripsCodes) . "&MS=wls2&MP=rect&MX=" . urlencode($size) . "&$pm&PC=%23ff0000&PW=2&RS=outline&RC=%23ff0000&RW=2");
