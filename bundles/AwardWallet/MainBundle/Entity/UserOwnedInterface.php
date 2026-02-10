<?php

namespace AwardWallet\MainBundle\Entity;

interface UserOwnedInterface
{
    /**
     * @return Usr
     */
    public function getUserid();

    /**
     * @return $this
     */
    public function setUserid(?Usr $user = null);

    /**
     * @return Useragent
     */
    public function getUseragentid();

    public function setUseragentid(?Useragent $useragent = null);
}
