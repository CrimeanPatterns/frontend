<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Buzz\Client\AbstractCurl;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use RMS\PushNotificationsBundle\Service\OS\AndroidGCMNotification;

class AndroidFCMLegacyNotification extends AndroidGCMNotification
{
    protected $apiURL = "https://fcm.googleapis.com/fcm/send";

    public function __construct($apiKey, $useMultiCurl, $timeout, $logger, ?AbstractCurl $client = null, $dryRun = false)
    {
        $logger = new class('android_proxy', [new PsrHandler($logger)]) extends Logger {
            public function addRecord($level, $message, array $context = [])
            {
                // vendor logging is too harsh
                if ($level >= self::ERROR) {
                    $level = self::INFO;
                }

                return parent::addRecord($level, $message, $context);
            }
        };

        parent::__construct($apiKey, $useMultiCurl, $timeout, $logger, $client, $dryRun);
    }
}
