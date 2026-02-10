<?php

namespace AwardWallet\MainBundle\Security\OAuth;

interface OAuthFactoryInterface
{
    public function getByType(string $type): BaseOAuth;
}
