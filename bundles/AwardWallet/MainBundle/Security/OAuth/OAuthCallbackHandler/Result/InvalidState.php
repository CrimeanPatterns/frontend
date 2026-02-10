<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

use AwardWallet\MainBundle\Globals\Singleton;

class InvalidState implements CallbackResultInterface
{
    use Singleton;
}
