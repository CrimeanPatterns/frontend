<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use Codeception\Module\Aw;

trait RandomUser
{
    /**
     * @return Usr
     */
    protected function createRandomUser(\TestSymfonyGuy $I, array $options = [], $isStaff = false)
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $login = substr($className, 0, 20) . StringHandler::getRandomCode(10);

        $id = $I->createAwUser(
            $login,
            Aw::DEFAULT_PASSWORD,
            array_merge(
                ['AccountLevel' => ACCOUNT_LEVEL_FREE],
                $options
            ),
            $isStaff
        );

        $user = $I->grabService('doctrine.orm.entity_manager')
            ->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
            ->find($id);

        return $user;
    }

    protected function removeUser(\TestSymfonyGuy $I, Usr $user)
    {
        $I->executeQuery("DELETE FROM BillingAddress WHERE UserID = " . $user->getUserid());
        $I->executeQuery("DELETE FROM Cart WHERE UserID = " . $user->getUserid());
        $I->executeQuery("DELETE FROM Usr WHERE UserID = " . $user->getUserid());
    }

    protected function loginUser(\TestSymfonyGuy $I, Usr $user)
    {
        $I->sendGET('/m/api/login_status?_switch_user=' . $user->getLogin());
    }

    protected function logoutUser(\TestSymfonyGuy $I)
    {
        $I->sendGET('/m/api/login_status?_switch_user=_exit');
    }
}
