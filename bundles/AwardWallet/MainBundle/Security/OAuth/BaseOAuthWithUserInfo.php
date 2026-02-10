<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class BaseOAuthWithUserInfo extends BaseOAuth
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(\HttpDriverInterface $httpDriver, LoggerInterface $logger, TranslatorInterface $translator)
    {
        parent::__construct($httpDriver, $logger);

        $this->translator = $translator;
    }

    public function exchangeCode(ExchangeCodeRequest $exchangeCodeRequest, ?array $rawCallbackData = null): ExchangeCodeResult
    {
        $result = $this->sendExchangeCodeRequest($exchangeCodeRequest->getCode(), $exchangeCodeRequest->getRedirectUrl());

        if ($result === null) {
            return new ExchangeCodeResult(null, null, $this->translator->trans('error.auth.failure'));
        }

        return $this->parseExchangeCodeResponse($result);
    }

    protected function parseExchangeCodeResponse(array $result): ExchangeCodeResult
    {
        $infoArray = $this->fetchUserInfo($result['access_token'], $this->getUserInfoUrl());
        $userInfo = $this->parseUserInfo($infoArray);

        if (empty($userInfo->getEmail())) {
            $this->logger->warning("empty email in user info", ["user_info" => $infoArray]);

            return new ExchangeCodeResult(null, null, $this->translator->trans('error.auth.failure'));
        }

        return new ExchangeCodeResult(
            $userInfo,
            new Tokens($result['access_token'], $result['refresh_token'] ?? null),
            null
        );
    }

    abstract protected function parseUserInfo(array $info): UserInfo;

    abstract protected function getUserInfoUrl(): string;

    private function fetchUserInfo(string $accessToken, string $url): array
    {
        $result = $this->sendRequest(new \HttpDriverRequest($url, 'GET', null, [
            "Authorization" => "Bearer " . $accessToken,
            "User-Agent" => "AwardWallet",
            "Accept" => "*/*",
        ]));

        if ($result === null) {
            throw new \Exception("failed to request user info");
        }

        return $result;
    }
}
