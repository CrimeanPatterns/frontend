<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Profile;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;

use function PHPUnit\Framework\assertEquals;

/**
 * Class PersonalCest.
 *
 * @group frontend-functional
 * @group mobile
 * @group security
 */
class PersonalCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    use JsonForm;

    public const ROUTE = '/m/api/profile/personal';

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $I->resetLockout('check_login', '127.0.0.1');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $I->resetLockout('check_login', '127.0.0.1');
    }

    public function changeCycle(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);
        $login = $this->user->getLogin();

        $I->sendPUT(self::ROUTE, [
            'login' => $newLogin = 'new' . substr($login, 3),
            'firstname' => $newFirst = 'new first',
            'lastname' => $newLast = 'new last',
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['needUpdate' => true, 'success' => true]);

        $I->seeInDatabase('Usr', [
            'Login' => $newLogin,
            'FirstName' => $newFirst,
            'LastName' => $newLast, ]
        );

        $I->sendPUT(self::ROUTE, [
            'login' => $login,
            'firstname' => $newFirst = 'new first 2',
            'lastname' => $newLast = 'new last 2',
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['needUpdate' => true, 'success' => true]);

        $I->seeInDatabase('Usr', [
            'Login' => $login,
            'FirstName' => $newFirst,
            'LastName' => $newLast, ]
        );
    }

    public function invalidLogin(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        $I->sendPUT(self::ROUTE, [
            'login' => 'abc',
            'firstname' => 'new first',
            'lastname' => 'new last',
            '_token' => $token,
        ]);

        assertEquals(
            'This value is too short. It should have 4 characters or more.',
            $this->grabFieldError($I, 'login')
        );

        $I->sendPUT(self::ROUTE, [
            'login' => 'abcвв',
            'firstname' => 'new first',
            'lastname' => 'new last',
            '_token' => $token,
        ]);

        assertEquals(
            'Please use only English letters or numbers. No Spaces.',
            $this->grabFieldError($I, 'login')
        );

        $I->sendPUT(self::ROUTE, [
            'login' => $this->createRandomUser($I)->getLogin(),
            'firstname' => 'new first',
            'lastname' => 'new last',
            '_token' => $token,
        ]);

        assertEquals(
            'This user name is already taken',
            $this->grabFieldError($I, 'login')
        );
    }

    /**
     * @group locks
     */
    public function multipleAttemptsShouldBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        foreach (range(1, 10) as $_) {
            $I->sendPUT(self::ROUTE, [
                'login' => 'abc',
                'firstname' => 'new first',
                'lastname' => 'new last',
                '_token' => $token,
            ]);

            assertEquals(
                'This value is too short. It should have 4 characters or more.',
                $this->grabFieldError($I, 'login')
            );
        }

        $I->sendPUT(self::ROUTE, [
            'login' => 'abc',
            'firstname' => 'new first',
            'lastname' => 'new last',
            '_token' => $token,
        ]);

        assertEquals(
            'Please wait 5 minutes before next attempt.',
            $this->grabFormError($I)
        );
    }
}
