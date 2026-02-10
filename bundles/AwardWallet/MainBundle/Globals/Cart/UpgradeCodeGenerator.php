<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\Usr;

class UpgradeCodeGenerator
{
    /**
     * @var string
     */
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function generateCode(Usr $user): string
    {
        return sha1("upgrade" . $user->getCreationdatetime()->getTimestamp() . $user->getSecret() . $this->secret);
    }
}
