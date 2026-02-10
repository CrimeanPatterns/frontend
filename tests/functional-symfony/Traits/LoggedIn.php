<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;

/**
 * Class LoggedIn.
 *
 * @property Usr $user
 * @property LazyVal $userLazy
 * @method loginUser(\TestSymfonyGuy $I, Usr $user)
 */
trait LoggedIn
{
    protected function _before_LoggedIn(\TestSymfonyGuy $I)
    {
        $this->loginUser($I, $this->userLazy->getValue());
    }
}
