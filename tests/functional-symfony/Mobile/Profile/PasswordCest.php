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
 * Class PasswordCest.
 *
 * @group frontend-functional
 * @group mobile
 * @group security
 */
class PasswordCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    use JsonForm;

    public const CHANGE_PASSWORD_ROUTE = '/m/api/profile/changePassword';

    public function newPasswordsMustMatch(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => 'one',
                'second' => 'two',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'Passwords must match',
            $this->grabFieldError($I, 'first')
        );
    }

    public function malformedNewPassword(\TestSymfonyGuy $I)
    {
        $I->specify(
            'malformed nested data',
            function ($data) use ($I) {
                $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
                $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
                    'oldPassword' => Aw::DEFAULT_PASSWORD,
                    'pass' => $data,
                    '_token' => $this->grabFormCsrfToken($I),
                ]);
                $I->dontSeeResponseContainsJson(['success' => true]);
            },
            ['examples' => [
                [null],
                [''],
                [[[], []]],
            ],
            ]
        );
    }

    public function invalidOldPassword(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => '0' . Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => 'one',
                'second' => 'two',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'Invalid password',
            $this->grabFieldError($I, 'oldPassword')
        );
    }

    public function emptyOldPassword(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'pass' => [
                'first' => 'one',
                'second' => 'two',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'This value should not be blank.',
            $this->grabFieldError($I, 'oldPassword')
        );
    }

    public function newPasswordSameAsOldPassword(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => Aw::DEFAULT_PASSWORD,
                'second' => Aw::DEFAULT_PASSWORD,
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'Your new password should not be the same as your old password.',
            $this->grabFieldError($I, 'first')
        );
    }

    /**
     * @group locks
     */
    public function multipleAttemptsShouldBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $token = $this->grabFormCsrfToken($I);

        foreach (range(1, 10) as $i) {
            $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
                'oldPassword' => $i . Aw::DEFAULT_PASSWORD,
                'pass' => [
                    'first' => 'one',
                    'second' => 'one',
                ],
                '_token' => $token,
            ]);

            assertEquals(
                'Invalid password',
                $this->grabFieldError($I, 'oldPassword')
            );
        }

        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => $i . Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => 'one',
                'second' => 'one',
            ],
            '_token' => $token,
        ]);

        assertEquals(
            'Your account has been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts.',
            $this->grabFormError($I)
        );
    }

    public function passwordChangeCycle(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $token = $this->grabFormCsrfToken($I);

        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => $newPassword = 'new ' . Aw::DEFAULT_PASSWORD,
                'second' => $newPassword,
            ],
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);

        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $token = $this->grabFormCsrfToken($I);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => $newPassword,
            'pass' => [
                'first' => $newPassword1 = 'new1 ' . Aw::DEFAULT_PASSWORD,
                'second' => $newPassword1,
            ],
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);

        $I->sendGET(self::CHANGE_PASSWORD_ROUTE);
        $token = $this->grabFormCsrfToken($I);
        $I->sendPUT(self::CHANGE_PASSWORD_ROUTE, [
            'oldPassword' => $newPassword1,
            'pass' => [
                'first' => Aw::DEFAULT_PASSWORD,
                'second' => Aw::DEFAULT_PASSWORD,
            ],
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);
    }

    public function forgotPassword(\TestSymfonyGuy $I)
    {
        $I->sendPOST('/m/api/profile/forgotPassword');
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/profile/forgotPassword');

        $I->seeEmailTo($this->user->getEmail(), 'Reset password to');
    }
}
