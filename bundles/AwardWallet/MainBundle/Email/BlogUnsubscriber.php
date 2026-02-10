<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

class BlogUnsubscriber
{
    private \HttpDriverInterface $httpDriver;
    private LoggerInterface $logger;
    private string $blogProtoAndHost;
    private string $blogApiSecret;

    public function __construct(\HttpDriverInterface $httpDriver, LoggerInterface $logger, string $protoAndHost, string $blogApiSecret)
    {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
        $this->blogProtoAndHost = $protoAndHost;
        $this->blogApiSecret = $blogApiSecret;
    }

    public function unsubscribe(string $email): void
    {
        $request = new \HttpDriverRequest($this->blogProtoAndHost . '/blog/wp-json/unsubscirbe/email', 'POST', ['email' => $email], ['Authorization' => 'Basic ' . base64_encode('awardwallet:' . $this->blogApiSecret)], 10);
        $response = $this->httpDriver->request($request);

        if ($response->httpCode != 200) {
            $this->logger->warning("failed to unsubscribe from blog, http code: {$response->httpCode}, network code: {$response->errorCode}, body: " . Strings::cutInMiddle($response->body, 512), ["email" => $email]);

            return;
        }

        $this->logger->info("successfully unsubscribed from blog", ["email" => $email]);
    }
}
