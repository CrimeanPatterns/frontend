<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MicrosoftOAuth extends BaseOAuthWithUserInfo
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
        return "microsoft";
    }

    protected function getBaseConsentUrl(): string
    {
        return "https://login.microsoftonline.com/common/oauth2/v2.0/authorize";
    }

    protected function getTokenRequestUrl(): string
    {
        return "https://login.microsoftonline.com/common/oauth2/v2.0/token";
    }

    protected function getUserInfoUrl(): string
    {
        return "https://graph.microsoft.com/v1.0/me";
    }

    protected function getScopes(bool $mailboxAccess, bool $profileAccess): array
    {
        $result = [];

        if ($profileAccess) {
            $result[] = "User.Read";
        }

        if ($mailboxAccess) {
            $result[] = "offline_access";
            $result[] = "Mail.Read";
            $result[] = "User.Read";
        }

        return array_unique($result);
    }

    protected function parseUserInfo(array $info): UserInfo
    {
        return new UserInfo($info['userPrincipalName'], $info['id'], $info['givenName'], $info['surname']);
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
