<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Usr;

trait UpdateUserIdTrait
{
    public function updateUserId(Usr $user, int $userId): Usr
    {
        $refl = new \ReflectionProperty(Usr::class, 'userid');
        $refl->setAccessible(true);
        $refl->setValue($user, $userId);
        $refl->setAccessible(false);

        return $user;
    }
}
