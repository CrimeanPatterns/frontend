<?php

namespace AwardWallet\MainBundle\Service\WebPush;

use Psr\Log\LoggerInterface;

class SafariPackageBuilder
{
    private string $certPath;

    private string $betaCertPath;

    private string $certPass;

    private string $sourceDir;

    private $rawFiles = [
        'icon.iconset/icon_16x16.png',
        'icon.iconset/icon_16x16@2x.png',
        'icon.iconset/icon_32x32.png',
        'icon.iconset/icon_32x32@2x.png',
        'icon.iconset/icon_128x128.png',
        'icon.iconset/icon_128x128@2x.png',
        'website.json',
    ];
    /**
     * @var array
     */
    private $hosts;

    private string $webSitePushId;
    private LoggerInterface $logger;

    public function __construct(
        string $webPushSafariCertPath,
        string $webPushSafariBetaCertPath,
        string $webPushSafariCertPass,
        string $host,
        string $businessHost,
        string $webPushId,
        LoggerInterface $logger
    ) {
        $this->certPath = $webPushSafariCertPath; // prod certificate: Dropbox/AWDevelopment/provisioningProfilesAndCerts/web.com.awardwallet.p12
        $this->betaCertPath = $webPushSafariBetaCertPath;
        $this->certPass = $webPushSafariCertPass;
        $this->sourceDir = __DIR__ . '/pushPackage.raw';
        $this->hosts = [$host, $businessHost];
        $this->webSitePushId = $webPushId;
        $this->logger = $logger;
    }

    public function build($userId, $host)
    {
        $packageDir = sys_get_temp_dir() . '/safariPackage_' . time() . '_' . rand(0, 999999);

        if (!mkdir($packageDir . '/icon.iconset', 0700, true)) {
            throw new \Exception('Package dir could not be created at ' . $packageDir);
        }

        foreach ($this->rawFiles as $file) {
            if ($file == "website.json") {
                $json = json_decode(file_get_contents("$this->sourceDir/$file"), true);
                $json["allowedDomains"] = array_map(function ($host) { return 'https://' . $host; }, $this->hosts);
                $json["authenticationToken"] = $userId;
                $json["webServiceURL"] = "https://" . $host . "/safari";
                $json["urlFormatString"] = "https://" . $host . "/%@";
                $json["websitePushID"] = $this->webSitePushId;
                file_put_contents("$packageDir/$file", json_encode($json, JSON_PRETTY_PRINT));
            } else {
                copy("$this->sourceDir/$file", "$packageDir/$file");
            }
        }

        // Create manifest
        $manifestData = [];

        foreach ($this->rawFiles as $file) {
            $manifestData[$file] = sha1(file_get_contents("$packageDir/$file"));
        }
        file_put_contents("$packageDir/manifest.json", json_encode((object) $manifestData));

        // Create signature
        $certFile = $this->certPath;
        $issuerCertFile = __DIR__ . "/AppleWWDRCAG4.pem";

        $beta = strpos($userId, 'aw:2110:') === 0;

        if ($beta) {
            $certFile = $this->betaCertPath;
            // $issuerCertFile = __DIR__ . "/AppleWWDRCAG4.pem";
        }

        //        getSymfonyContainer()->get("logger")->info("userId: {$userId}, safari cert path: {$certFile}, issuer cert: {$issuerCertFile}");
        $this->logger->info("reading certificate $certFile");
        $pkcs12 = file_get_contents($certFile);
        $certs = [];

        if (!openssl_pkcs12_read($pkcs12, $certs, $this->certPass)) {
            throw new \Exception('Unable to read the cert store: ' . openssl_error_string());
        }

        $signaturePath = "$packageDir/signature";
        $certData = openssl_x509_read($certs['cert']);
        $privateKey = openssl_pkey_get_private($certs['pkey'], $this->certPass);
        openssl_pkcs7_sign("$packageDir/manifest.json", $signaturePath, $certData, $privateKey, [], PKCS7_BINARY | PKCS7_DETACHED, $issuerCertFile);

        // Convert the signature from PEM to DER
        $signaturePem = file_get_contents($signaturePath);
        $matches = [];

        if (!preg_match('~Content-Disposition:[^\n]+\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----~', $signaturePem, $matches)) {
            return false;
        }
        $signatureDer = base64_decode($matches[1]);
        file_put_contents($signaturePath, $signatureDer);

        // Zips the directory structure into a push package, and returns the path to the archive.
        $zipPath = "$packageDir.zip";
        $zip = new \ZipArchive();

        if (!$zip->open("$packageDir.zip", $zip::CREATE)) {
            throw new \Exception('Could not create ' . $zipPath);
        }
        $rawFiles = $this->rawFiles;
        $rawFiles[] = 'manifest.json';
        $rawFiles[] = 'signature';

        foreach ($rawFiles as $file) {
            $zip->addFile("$packageDir/$file", $file);
        }
        $zip->close();

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($packageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($packageDir);

        return $zipPath;
    }
}
