<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

interface CallbackErrorInterface extends CallbackResultInterface
{
    public function getTextError(): ?string;
}
