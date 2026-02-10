<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

use AwardWallet\MainBundle\Entity\Usr;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

trait FreeUser
{
    use RandomUser;

    /**
     * @var Usr
     */
    protected $user;

    protected $userLazy;

    protected function _lazy_FreeUser(\TestSymfonyGuy $I)
    {
        $this->userLazy = $this->userLazy ?? lazy(function () use ($I) {
            return $this->user = $this->createRandomUser($I, ['AccountLevel' => ACCOUNT_LEVEL_FREE]);
        });
    }

    protected function _before_FreeUser(\TestSymfonyGuy $I)
    {
        $this->userLazy->getValue();
    }

    protected function _after_FreeUser(\TestSymfonyGuy $I)
    {
        if ($this->user) {
            $this->removeUser($I, $this->user);
        }
        unset($this->user);
        unset($this->userLazy);
    }
}
