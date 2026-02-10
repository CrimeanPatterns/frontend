<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Profile;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Codeception\Module\Aw;

use function PHPUnit\Framework\assertEquals;

/**
 * Class EmailCest.
 *
 * @group frontend-functional
 * @group security
 * @group mobile
 */
class EmailCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    use JsonForm;

    public const ROUTE = '/m/api/profile/changeEmail';

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $I->resetLockout('check_email', '127.0.0.1');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $I->resetLockout('check_email', '127.0.0.1');
    }

    public function invalidPassword(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $I->sendPUT(self::ROUTE, [
            'password' => '0' . Aw::DEFAULT_PASSWORD,
            'email' => 'testEmail@awardwallet.com',
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'Invalid password',
            $this->grabFieldError($I, 'password')
        );
    }

    public function invalidEmail(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $I->sendPUT(self::ROUTE, [
            'password' => Aw::DEFAULT_PASSWORD,
            'email' => 'testEmail_awardwallet.com',
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'This is not a valid email address',
            $this->grabFieldError($I, 'email')
        );

        $I->sendPUT(self::ROUTE, [
            'password' => Aw::DEFAULT_PASSWORD,
            'email' => $this->createRandomUser($I)->getEmail(),
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'This email is already taken',
            $this->grabFieldError($I, 'email')
        );
    }

    public function emailChangeCycle(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);
        $email = $this->user->getEmail();

        $I->sendPUT(self::ROUTE, [
            'password' => Aw::DEFAULT_PASSWORD,
            'email' => $newEmail = 'new' . $email,
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($newEmail, 'Email verification request from');

        $I->sendPUT(self::ROUTE, [
            'password' => Aw::DEFAULT_PASSWORD,
            'email' => $newEmail = 'new1' . $email,
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($newEmail, 'Email verification request from');

        $I->sendPUT(self::ROUTE, [
            'password' => Aw::DEFAULT_PASSWORD,
            'email' => $email,
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/profile/sendEmail');
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($email, 'Email verification request from');
    }

    /**
     * @group locks
     */
    public function multiplePasswordAttemptsShouldBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        foreach (range(1, 10) as $_) {
            $I->sendPUT(
                self::ROUTE,
                [
                    'password' => $_ . Aw::DEFAULT_PASSWORD,
                    'email' => 'testEmail@awardwallet.com',
                    '_token' => $token,
                ]
            );

            assertEquals(
                'Invalid password',
                $this->grabFieldError($I, 'password')
            );
        }

        $I->sendPUT(
            self::ROUTE,
            [
                'password' => Aw::DEFAULT_PASSWORD,
                'email' => 'testEamil@awardwallet.com',
                '_token' => $token,
            ]
        );

        assertEquals(
            'Your account has been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts.',
            $this->grabFieldError($I, 'password')
        );
    }

    /**
     * @group locks
     */
    public function multipleEmailAttemptsShoulBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        foreach (range(1, 10) as $_) {
            $I->sendPUT(
                self::ROUTE,
                [
                    'password' => Aw::DEFAULT_PASSWORD,
                    'email' => $this->createRandomUser($I)->getEmail(),
                    '_token' => $token,
                ]
            );

            assertEquals(
                'This email is already taken',
                $this->grabFieldError($I, 'email')
            );
        }

        $I->sendPUT(
            self::ROUTE,
            [
                'password' => Aw::DEFAULT_PASSWORD,
                'email' => $this->createRandomUser($I)->getEmail(),
                '_token' => $this->grabFormCsrfToken($I),
            ]
        );

        assertEquals(
            'Please wait 5 minutes before next attempt.',
            $this->grabFormError($I)
        );
    }
}
