<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class GoogleOAuth extends BaseOAuth
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        \HttpDriverInterface $httpDriver,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        string $clientId,
        string $clientSecret
    ) {
        parent::__construct($httpDriver, $logger);

        $this->translator = $translator;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getType(): string
    {
        return 'google';
    }

    public function exchangeCode(ExchangeCodeRequest $exchangeCodeRequest, ?array $rawCallbackData = null): ExchangeCodeResult
    {
        $result = $this->sendExchangeCodeRequest($exchangeCodeRequest->getCode(), $exchangeCodeRequest->getRedirectUrl());

        if ($result === null) {
            return new ExchangeCodeResult(null, null, $this->translator->trans('error.auth.failure'));
        }

        $this->logger->info('google exchange tokens', [
            'access_token' => Strings::cutInMiddle($result['access_token'], 5),
            'refresh_token' => isset($result['refresh_token']) ?
                Strings::cutInMiddle($result['refresh_token'], 5) :
                'aw: refresh token is absent',
            'scopes' => $result['scope'] ?? '',
        ]);

        $scopes = explode(" ", $result['scope'] ?? '');

        $mailboxAccess = in_array(\Google_Service_Gmail::GMAIL_READONLY, $scopes);
        $profileAccess = in_array(\Google_Service_Oauth2::USERINFO_PROFILE, $scopes);

        if ($profileAccess) {
            $userInfo = $this->getUserInfo($result['access_token']);
        } else {
            $userInfo = $this->getUserInfoEmailOnly($result['access_token']);
        }

        if ($userInfo === null) {
            return new ExchangeCodeResult(null, null, $this->translator->trans('error.auth.failure'));
        }

        return new ExchangeCodeResult(
            $userInfo,
            new Tokens($result['access_token'], $result['refresh_token'] ?? null),
            null,
            $mailboxAccess
        );
    }

    protected function getUserInfo(string $accessToken): UserInfo
    {
        $fetchedBase = $this->fetch('https://www.googleapis.com/oauth2/v3/userinfo', $accessToken);

        if ($fetchedBase === null) {
            throw new \Exception("failed to request user info");
        }
        $this->logger->info("fetched user info: " . json_encode($fetchedBase));

        $avatar = null;
        $fetchedPhotos = $this->fetch(
            'https://people.googleapis.com/v1/people/me?personFields=photos',
            $accessToken
        );
        $this->logger->info("request people/me result: " . json_encode($fetchedPhotos !== null));

        if (is_array($fetchedPhotos) && isset($fetchedPhotos['photos']) && is_array($fetchedPhotos['photos'])) {
            $avatar = it($fetchedPhotos['photos'])
                ->filter(function ($photo) {
                    return
                        isset($photo['metadata']) && is_array($photo['metadata'])
                        && isset($photo['metadata']['primary']) && $photo['metadata']['primary'] === true
                        && (!isset($photo['default']) || $photo['default'] === false)
                        && isset($photo['url']);
                })
                ->map(function ($photo) {
                    return $photo['url'];
                })
                ->first();
        }

        return new UserInfo(
            $fetchedBase["email"],
            $fetchedBase['sub'],
            $fetchedBase["given_name"] ?? null,
            $fetchedBase["family_name"] ?? null,
            $avatar
        );
    }

    protected function getBaseConsentUrl(): string
    {
        return "https://accounts.google.com/o/oauth2/auth";
    }

    protected function getTokenRequestUrl(): string
    {
        return "https://accounts.google.com/o/oauth2/token";
    }

    protected function getScopes(bool $mailboxAccess, bool $profileAccess): array
    {
        $result = [];

        if ($mailboxAccess) {
            $result[] = \Google_Service_Gmail::GMAIL_READONLY;
        }

        if ($profileAccess) {
            $result[] = \Google_Service_Oauth2::USERINFO_PROFILE;
            $result[] = \Google_Service_Oauth2::USERINFO_EMAIL;
        }

        return array_unique($result);
    }

    protected function getClientId(): string
    {
        return $this->clientId;
    }

    protected function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    protected function getExtraConsentUrlParams(): array
    {
        if (isset($_COOKIE['enable_granular_consent'])) {
            return ['enable_granular_consent' => 'true'];
        }

        return [];
    }

    private function getUserInfoEmailOnly(string $accessToken): ?UserInfo
    {
        $fetchedBase = $this->fetch('https://gmail.googleapis.com/gmail/v1/users/me/profile', $accessToken);

        if ($this->lastResponse->httpCode === 400) {
            $response = @json_decode($this->lastResponse->body, true);

            if (is_array($response)
                && isset($response['error']['message'])
                && $response['error']['message'] === 'Mail service not enabled') {
                $this->logger->warning("failed to get user info: mail service not enabled");

                return null;
            }
        }

        if ($fetchedBase === null) {
            throw new \Exception("failed to request user info");
        }

        $this->logger->info("fetched user info: " . json_encode($fetchedBase));

        return new UserInfo(
            $fetchedBase["emailAddress"],
            null,
            null,
            null,
            null
        );
    }

    private function fetch(string $url, string $accessToken): ?array
    {
        return $this->sendRequest(
            new \HttpDriverRequest($url, 'GET', null, [
                "Authorization" => "Bearer " . $accessToken,
                "User-Agent" => "AwardWallet",
                "Accept" => "*/*",
            ])
        );
    }
}
