<?php

namespace AwardWallet\MainBundle\Service\EmailParsing;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class EmailScannerApiFactory
{
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @return EmailScannerApi
     */
    public function getApi($timeout = 30, array $clientOptions = [])
    {
        $client = new Client(
            \array_merge(
                [RequestOptions::TIMEOUT => $timeout, RequestOptions::CONNECT_TIMEOUT => $timeout],
                $clientOptions
            ));

        return new EmailScannerApi($client, $this->config);
    }
}
