<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

class CamoufoxBrowser
{
    private string $endpoint;
    private \HttpDriverInterface $httpDriver;
    private LoggerInterface $logger;

    public function __construct(string $camoufoxEndpoint, \HttpDriverInterface $httpDriver, LoggerInterface $logger)
    {
        $this->endpoint = $camoufoxEndpoint;
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
    }

    public function navigate(string $url): ?CamoufoxResponse
    {
        $request = new \HttpDriverRequest($this->endpoint . "/navigate", "POST", json_encode(["url" => $url]), ["Content-Type" => "application/json"], 180);
        $response = $this->httpDriver->request($request);

        if ($response->httpCode !== 200) {
            $this->logger->error("failed to navigate: " . $response->httpCode . " " . Strings::cutInMiddle($response->body, 200) . ", network error: " . $response->errorCode);

            return null;
        }

        return new CamoufoxResponse(json_decode($response->body, true)['html']);
    }
}
