<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

use AwardWallet\MainBundle\Globals\Singleton;

class UnauthorizedUser implements CallbackResultInterface
{
    use Singleton;
}
