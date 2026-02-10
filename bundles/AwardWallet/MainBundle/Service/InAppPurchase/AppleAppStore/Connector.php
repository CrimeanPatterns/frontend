<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore;

use AwardWallet\MainBundle\Service\InAppPurchase\Exception\ConnectException;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use Buzz\Message\Request;
use Buzz\Message\Response;

class Connector
{
    private Curl $client;

    private int $waitBeforeRetry = 5;

    public function __construct(Curl $client)
    {
        $this->client = $client;
    }

    public function setClient(Curl $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function setWaitBeforeRetry(int $waitBeforeRetry): self
    {
        $this->waitBeforeRetry = $waitBeforeRetry;

        return $this;
    }

    /**
     * @throws ConnectException
     */
    public function sendRequest(string $url, string $content): ?string
    {
        $response = null;
        $this->client->setTimeout(60);
        $this->client->setMaxRedirects(3);

        for ($n = 0; ($n < 3) && ($n === 0 || isset($error)); $n++) {
            if ($n > 0) {
                sleep($this->waitBeforeRetry);
            }
            $error = null;
            $request = new Request(Request::METHOD_POST, $url);
            $request->setContent($content);
            $request->setHeaders(['Content-Type' => 'application/json; charset=utf-8']);
            $response = new Response();

            try {
                $this->client->send($request, $response);
            } catch (RequestException $e) {
                $response = null;
                $error = $e->getMessage();

                continue;
            }
            $response = $response->getContent();

            if (empty($response)) {
                $error = 'Invalid response';
            }
        }

        if (isset($error)) {
            throw new ConnectException($error);
        }

        return $response;
    }
}
