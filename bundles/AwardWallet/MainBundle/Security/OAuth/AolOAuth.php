<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AolOAuth extends BaseOAuthWithUserInfo
{
    /**
     * @var string
     */
    private $clientId;
    /**
     * @var string
     */
    private $clientSecret;

    public function __construct(string $clientId, string $clientSecret, \HttpDriverInterface $httpDriver, LoggerInterface $logger, TranslatorInterface $translator)
    {
        parent::__construct($httpDriver, $logger, $translator);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getType(): string
    {
        return 'aol';
    }

    protected function getBaseConsentUrl(): string
    {
        return "https://api.login.aol.com/oauth2/request_auth";
    }

    protected function getTokenRequestUrl(): string
    {
        return "https://api.login.aol.com/oauth2/get_token";
    }

    protected function getUserInfoUrl(): string
    {
        return "https://api.login.aol.com/openid/v1/userinfo";
    }

    protected function getScopes(bool $mailboxAccess, bool $profileAccess): array
    {
        $result = [];

        if ($profileAccess) {
            $result[] = "profile";
            $result[] = "email";
        }

        if ($mailboxAccess) {
            $result[] = "email";
            $result[] = "mail-r";
        }

        return array_unique($result);
    }

    protected function parseUserInfo(array $info): UserInfo
    {
        $defaultName = explode("@", $info['email']);
        $avatar = $info["picture"] ?? null;

        if ($avatar && strpos($avatar, 'default_user_profile_pic') !== false) {
            $avatar = null;
        }

        return new UserInfo(
            $info["email"],
            $info["sub"],
            $info["given_name"] ?? $defaultName[0], // yahoo could return profile without first name / last name
            $info["family_name"] ?? $defaultName[1],
            $avatar
        );
    }

    protected function getClientId(): string
    {
        return $this->clientId;
    }

    protected function getClientSecret(): string
    {
        return $this->clientSecret;
    }
}
