<?php

use AwardWallet\MainBundle\Service\SecureLink;

require "kernel/public.php";

function validate($url)
{
    $file = tempnam(sys_get_temp_dir(), "image-proxy");
    register_shutdown_function(function() use ($file) {
        unlink($file);
    });
    if (!($fp = @fopen($file, 'wb'))) {
        badRequest();
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (!file_exists($file) || !is_file($file)) {
        badRequest();
    }

    $imgType = @exif_imagetype($file);
    if (!$imgType) {
        badRequest();
    }

    return [$file, $imgType];
}

function badRequest()
{
    header("HTTP/1.1 400 Bad Request");
    exit();
}

if (
    !isset($_GET['url']) ||
    !is_string($_GET['url']) ||
    empty($_GET['url']) ||
    !filter_var($_GET['url'], FILTER_VALIDATE_URL) ||
    !isset($_GET['hash']) ||
    !is_string($_GET['hash']) ||
    empty($_GET['hash'])
) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$url = $_GET['url'];
$hash = $_GET['hash'];

$checker = getSymfonyContainer()->get(SecureLink::class);
if (!$checker->checkImgUrlHash($url, $hash)) {
    badRequest();
}

list($fileName, $imageType) = validate($url);

header('Content-Type: ' . image_type_to_mime_type($imageType));
header("Cache-control: public");
header("Cache-control: max-age=7200");

readfile($fileName);
