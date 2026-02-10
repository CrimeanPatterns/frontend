<?php

namespace AwardWallet\MainBundle\Service\MailChimp;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class MailChimp
{
    private string $apiKey;

    private string $listId;

    private string $endpoint;

    private \HttpDriverInterface $httpDriver;

    private LoggerInterface $logger;

    public function __construct(
        string $byaMailchimpApiKey,
        string $byaMailchimpListId,
        string $byaMailchimpEndpoint,
        \HttpDriverInterface $curlDriver,
        LoggerInterface $logger
    ) {
        $this->apiKey = $byaMailchimpApiKey;
        $this->listId = $byaMailchimpListId;
        $this->endpoint = $byaMailchimpEndpoint;
        $this->httpDriver = $curlDriver;
        $this->logger = $logger;
    }

    public function call($route, $method, $payload = [])
    {
        $response = $this->httpDriver->request(new \HttpDriverRequest(
            $this->endpoint . $route,
            $method,
            json_encode($payload),
            [
                'Authorization' => 'Basic ' . base64_encode("bya:" . $this->apiKey),
            ],
            30)
        );

        if ($response->httpCode !== Response::HTTP_OK && $response->httpCode !== Response::HTTP_BAD_REQUEST) {
            $this->logger->critical('Mailchimp API error', [
                'payload' => $payload,
                'response' => $response->body,
            ]);
        }

        return json_decode($response->body, true);
    }

    public function getLists()
    {
        return $this->call('lists', 'GET');
    }

    public function execute(Task $task, $delay = null)
    {
        return $this->call(
            "lists/{$this->listId}/members",
            'POST',
            $task->parameters[0]);
    }
}
