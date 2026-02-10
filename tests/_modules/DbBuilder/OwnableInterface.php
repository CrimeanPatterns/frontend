<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

interface OwnableInterface
{
    /**
     * @return User|UserAgent|null
     */
    public function getUser();

    /**
     * @param User|UserAgent|null $user
     */
    public function setUser($user): self;
}
