<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

abstract class BaseOAuth
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected ?\HttpDriverResponse $lastResponse = null;

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;

    public function __construct(\HttpDriverInterface $httpDriver, LoggerInterface $logger)
    {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
    }

    public function getConsentUrl(
        string $state,
        string $redirectUrl,
        bool $mailboxAccess,
        bool $profileAccess,
        ?string $loginHint
    ) {
        $params = [
            "response_type" => "code",
            "client_id" => $this->getClientId(),
            "redirect_uri" => $redirectUrl,
            "scope" => implode(" ", $this->getScopes($mailboxAccess, $profileAccess)),
            "state" => $state,
            "prompt" => "consent",
        ];
        $params = array_merge($params, $this->getExtraConsentUrlParams());

        if ($mailboxAccess) {
            $params["access_type"] = "offline";
        }

        if ($loginHint !== null) {
            $params['login_hint'] = $loginHint;
        }

        return $this->getBaseConsentUrl() . "?" . http_build_query($params);
    }

    abstract public function exchangeCode(ExchangeCodeRequest $exchangeCodeRequest, ?array $rawCallbackData = null): ExchangeCodeResult;

    abstract public function getType(): string;

    abstract protected function getBaseConsentUrl(): string;

    abstract protected function getTokenRequestUrl(): string;

    abstract protected function getScopes(bool $mailboxAccess, bool $profileAccess): array;

    protected function getExtraConsentUrlParams(): array
    {
        return [];
    }

    protected function sendRequest(\HttpDriverRequest $request): ?array
    {
        $response = $this->httpDriver->request($request);
        $this->lastResponse = $response;

        if ($this->lastResponse->httpCode === 429) {
            $body = @json_decode($this->lastResponse->body, true);

            if (is_array($body)
                && isset($body['error']['code'])
                && $body['error']['code'] == 429) {
                $this->logger->warning("429, try again");
                sleep(random_int(1, 5));
                $response = $this->httpDriver->request($request);
                $this->lastResponse = $response;
            }
        }

        if ($response->httpCode < 200 || $response->httpCode >= 300) {
            $this->logger->warning("got http {$response->httpCode} while requesting {$request->url}: " . Strings::cutInMiddle($response->body, 300));

            return null;
        }

        $result = @json_decode($response->body, true);

        if (!is_array($result) || !count($result) === 0) {
            $this->logger->warning("failed to decode response while requesting {$request->url}: " . Strings::cutInMiddle($response->body, 300));

            return null;
        }

        return $result;
    }

    protected function sendExchangeCodeRequest(string $code, string $redirectUri): ?array
    {
        $params = [
            "code" => $code,
            "client_id" => $this->getClientId(),
            "client_secret" => $this->getClientSecret(),
            "redirect_uri" => $redirectUri,
            "grant_type" => "authorization_code",
        ];

        return $this->sendRequest(new \HttpDriverRequest($this->getTokenRequestUrl(), 'POST', $params));
    }

    abstract protected function getClientId(): string;

    abstract protected function getClientSecret(): string;
}
