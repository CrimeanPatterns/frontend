<?php

namespace AwardWallet\MainBundle\Service\Tripit;

class OAuthConsumerCredentialFactory
{
    private string $consumerKey;
    private string $consumerSecret;

    public function __construct(string $tripitConsumerKey, string $tripitConsumerSecret)
    {
        $this->consumerKey = $tripitConsumerKey;
        $this->consumerSecret = $tripitConsumerSecret;
    }

    public function create(TripitUser $user): OAuthConsumerCredential
    {
        $credential = new OAuthConsumerCredential($this->consumerKey, $this->consumerSecret);

        if ($user->hasRequestTokens()) {
            $credential->setOauthToken($user->getRequestToken());
            $credential->setOauthTokenSecret($user->getRequestSecret());
        } elseif ($user->hasAccessTokens()) {
            $credential->setOauthToken($user->getAccessToken());
            $credential->setOauthTokenSecret($user->getAccessSecret());
        }

        return $credential;
    }
}
