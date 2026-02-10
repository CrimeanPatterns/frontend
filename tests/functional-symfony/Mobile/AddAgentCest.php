<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Module\Aw;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

/**
 * @group frontend-functional
 * @group mobile
 */
class AddAgentCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;
    use JsonHeaders;
    use JsonForm;

    public const ROUTE = '/m/api/agent/add';

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $I->resetLockout('add_connection', '127.0.0.1');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $I->resetLockout('add_connection', '127.0.0.1');
    }

    public function addAgentSuccess(\TestSymfonyGuy $I)
    {
        $I->specify(
            'success save',
            function ($inviteByEmail, $email) use ($I) {
                $I->sendGET(self::ROUTE);
                $I->sendPOST(self::ROUTE, $formData = [
                    'firstname' => $first = 'first' . StringHandler::getRandomCode(10),
                    'lastname' => 'last',
                    'email' => $email,
                    'invite' => $inviteByEmail,
                    '_token' => $this->grabFormCsrfToken($I),
                ]);

                $agentId = $I->grabDataFromResponseByJsonPath('$.result.owner')[0];

                $I->seeInDatabase('UserAgent', [
                    'UserAgentID' => explode('_', $agentId)[1],
                    'FirstName' => $first,
                    'LastName' => 'last',
                    'Email' => ('' === $email) ? null : $email,
                    'AgentID' => $this->user->getUserid(),
                ]);
                $I->shouldHaveInDatabase('UserAgent', ['UserAgentID' => explode('_', $agentId)[1]]);

                if ($inviteByEmail) {
                    $I->seeEmailTo($email, 'Invitation to AwardWallet.com');
                } else {
                    $I->dontSeeEmailTo($email, 'Invitation to AwardWallet.com');
                    $I->dontSeeEmailTo($email, 'Invitation to claim ownership of your reward programs');
                }
            },
            [
                /** [inviteByEmail, email] */
                'examples' => [
                    [true, 'first' . StringHandler::getRandomCode(10) . '@awardwallet.com'], // invite
                    [false, 'first' . StringHandler::getRandomCode(10) . '@awardwallet.com'], // do not invite, but save email
                    [false, ''], // do not invite
                ],
            ]
        );
    }

    public function addAgentInvalidData(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        $I->specify(
            'failed data',
            function (
                $first,
                $last,
                $email,
                $invite,
                $errors
            ) use ($I) {
                $I->sendPOST(self::ROUTE, $formData = [
                    'firstname' => $first,
                    'lastname' => $last,
                    'email' => $email,
                    'invite' => $invite,
                    '_token' => $this->grabFormCsrfToken($I),
                ]);

                foreach ($errors as $errorField) {
                    if ('form' === $errorField) {
                        assertNotEmpty($this->grabFormError($I));
                    } else {
                        assertNotEmpty($this->grabFieldError($I, $errorField));
                    }
                }
            },
            [
                /** [firstname, lastname, email, invite, [errorpath]] */
                'examples' => [
                    ['',      'last', 'email@email.com', false, ['firstname']],
                    ['first', '',     'email@email.com', false, ['lastname']],
                    ['first', 'last', 'email_email.com', false, ['email']],
                    ['first', 'last', 'email_email.com', true,  ['email']],
                    ['first', 'last', '',                true,  ['email']],
                ],
            ]
        );
    }

    /**
     * @group locks
     */
    public function multipleAttemptsFromSameUserShouldBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first',
            'lastname' => 'last',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token,
        ]);

        foreach (range(1, 9) as $_) {
            $I->sendPOST(self::ROUTE, $formData = [
                'firstname' => 'first',
                'lastname' => 'last',
                'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
                'invite' => false,
                '_token' => $token,
            ]);

            $I->resetLockout('add_connection', '127.0.0.1');

            assertEquals('User first last already registered in your profile', $this->grabFormError($I));
        }

        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first_x',
            'lastname' => 'last_x',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token,
        ]);

        assertEquals('Please wait 10 minutes before next attempt.', $this->grabFormError($I));
    }

    /**
     * @group locks
     */
    public function multipleAttemptsFromSameIpShouldBeLocked(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::ROUTE);
        $token = $this->grabFormCsrfToken($I);

        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first',
            'lastname' => 'last',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token,
        ]);

        foreach (range(1, 9) as $_) {
            $I->sendPOST(self::ROUTE, $formData = [
                'firstname' => 'first',
                'lastname' => 'last',
                'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
                'invite' => false,
                '_token' => $token,
            ]);

            $I->resetLockout('add_connection', (string) $this->user->getUserid());

            assertEquals('User first last already registered in your profile', $this->grabFormError($I));
        }

        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first_x',
            'lastname' => 'last_x',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token,
        ]);

        assertEquals('Please wait 10 minutes before next attempt.', $this->grabFormError($I));
    }

    public function maxAgentCountCheck(\TestSymfonyGuy $I)
    {
        foreach (range(1, 19) as $_) {
            $I->haveInDatabase('UserAgent', [
                'FirstName' => 'first' . $_,
                'LastName' => 'last' . $_,
                'Email' => StringHandler::getRandomCode(10) . '@awardwallet.com',
                'AgentID' => $this->user->getUserid(),
                'IsApproved' => 1,
            ]);
        }

        $I->sendGET(self::ROUTE);
        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first0',
            'lastname' => 'last0',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token = $this->grabFormCsrfToken($I),
        ]);

        $I->seeResponseContainsJson(['success' => true]);

        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first_fail',
            'lastname' => 'last_fail',
            'email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'invite' => false,
            '_token' => $token,
        ]);

        assertStringContainsString('business', $this->grabFormError($I));
    }

    public function agentExistsCheck(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase('UserAgent', [
            'FirstName' => 'first',
            'LastName' => 'last',
            'Email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'AgentID' => $this->user->getUserid(),
            'IsApproved' => 1,
        ]);

        $I->sendGET(self::ROUTE);
        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first',
            'lastname' => 'last',
            'email' => $email,
            'invite' => false,
            '_token' => $this->grabFormCsrfToken($I),
        ]);

        assertEquals(
            'User first last already registered in your profile',
            $this->grabFormError($I)
        );
    }

    public function sameAgentExistsInFriendsConnections(\TestSymfonyGuy $I)
    {
        $familyMemberId = $I->haveInDatabase('UserAgent', [
            'FirstName' => 'first',
            'LastName' => 'last',
            'Email' => $email = StringHandler::getRandomCode(10) . '@awardwallet.com',
            'AgentID' => $this->user->getUserid(),
            'IsApproved' => 1,
        ]);
        $I->saveCsrfToken();
        $I->sendPOST("/m/api/connections/family-member/invite/{$familyMemberId}", ['email' => $email]);
        $I->seeResponseContainsJson(['success' => true]);
        $inviteCode = $I->grabFromDatabase('Invites', 'Code', ['Email' => $email, 'Approved' => false]);

        $newUser = $this->createRandomUser($I);
        $this->logoutUser($I);
        $this->loginUser($I, $newUser);
        $I->saveCsrfToken();
        $I->sendPOST("/m/api/connections/invite/confirm/{$inviteCode}");
        $I->seeResponseContainsJson(['success' => true]);

        $I->sendGET(self::ROUTE);
        $I->sendPOST(self::ROUTE, $formData = [
            'firstname' => 'first',
            'lastname' => 'last',
            'email' => $email,
            'invite' => false,
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function accountChoiceWithFormLink(\TestSymfonyGuy $I)
    {
        // old version choice
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.14.0+abcdef');
        $I->sendGET('/m/api/provider/' . Aw::TEST_PROVIDER_ID);
        $I->dontSeeResponseJsonMatchesJsonPath('$..[?(@.name = "owner")].formLinks.new_family_member');
        $I->dontSeeResponseJsonMatchesJsonPath('$..[?(@.name = "owner")].choices..[?(@.label = "Add new person")]');

        // new version choice
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.15.0+abcdef');
        $I->sendGET('/m/api/provider/' . Aw::TEST_PROVIDER_ID);
        $I->seeResponseJsonMatchesJsonPath('$..[?(@.name = "owner")].choices..[?(@.label = "Add new person")]');
        $I->seeResponseJsonMatchesJsonPath('$..[?(@.name = "owner")].formLinks.new_family_member');
    }

    public function accountFormCallback(\TestSymfonyGuy $I)
    {
        $useragentId = $I->haveInDatabase('UserAgent', [
            'FirstName' => 'first',
            'LastName' => 'last',
            'Email' => StringHandler::getRandomCode(10) . '@awardwallet.com',
            'AgentID' => $this->user->getUserid(),
            'IsApproved' => 1,
        ]);
        $userId = $this->user->getUserid();

        $object = ['owner' => $userId . '_' . $useragentId];
        $I->sendGET('/m/api/provider/' . Aw::TEST_PROVIDER_ID);
        assertTrue($I->grabDataFromResponseByJsonPath('$..[?(@.name = "owner")].choices[?(@.value = "' . $userId . '")].selected')[0]);

        $I->sendGET('/m/api/provider/' . Aw::TEST_PROVIDER_ID . '/' . base64_encode(json_encode($object)));
        assertTrue($I->grabDataFromResponseByJsonPath('$..[?(@.name = "owner")].choices[?(@.value = "' . $userId . '_' . $useragentId . '")].selected')[0]);

        $accountId = $I->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'balance.random');
        $I->sendGET('/m/api/account/' . $accountId);
        assertTrue($I->grabDataFromResponseByJsonPath('$..[?(@.name = "owner")].choices[?(@.value = "' . $userId . '")].selected')[0]);

        $I->sendGET('/m/api/account/' . $accountId . '/' . base64_encode(json_encode($object)));
        assertTrue($I->grabDataFromResponseByJsonPath('$..[?(@.name = "owner")].choices[?(@.value = "' . $userId . '_' . $useragentId . '")].selected')[0]);
    }
}
