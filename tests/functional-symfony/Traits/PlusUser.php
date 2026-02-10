<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

use AwardWallet\MainBundle\Entity\Usr;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

trait PlusUser
{
    use RandomUser;

    /**
     * @var Usr
     */
    protected $user;

    protected $userLazy;

    protected function _lazy_PlusUser(\TestSymfonyGuy $I)
    {
        $this->userLazy = $this->userLazy ?? lazy(function () use ($I) {
            return $this->user = $this->createRandomUser($I, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        });
    }

    protected function _before_PlusUser(\TestSymfonyGuy $I)
    {
        $this->userLazy->getValue();
    }

    protected function _after_PlusUser(\TestSymfonyGuy $I)
    {
        if ($this->user) {
            $this->removeUser($I, $this->user);
        }
        unset($this->user);
        unset($this->userLazy);
    }
}
