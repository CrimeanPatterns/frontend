<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class UpdaterControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function updateNormalLimit(\TestSymfonyGuy $I)
    {
        $I->wantToTest('that account update will return an error upon exceeding the update limit');
        $accountId = $I->createAwAccount($this->user->getUserid(), 'testprovider', 'future.reservation');
        $I->amOnPage("/account/list/");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');

        // First updates
        for ($i = 0; $i < 2; $i++) {
            $I->sendPOST('/account/updater/start', json_encode([
                'accounts' => [$accountId],
                'startKey' => rand(0, 10000000),
                'source' => 'one',
            ]));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
        // Third update - should fail now
        $I->sendPOST('/account/updater/start', json_encode([
            'accounts' => [$accountId],
            'startKey' => rand(0, 10000000),
            'source' => 'one',
        ]));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='fail')]");
    }

    public function updateAWPlusLimit(\TestSymfonyGuy $I)
    {
        $I->wantToTest('that account may be updated without limits with AW+');
        $userId = $I->createAwUser($I->grabRandomString(10), 'Password', ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], true);
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($userId);
        $accountId = $I->createAwAccount($userId, 'testprovider', 'future.reservation');
        $this->logoutUser($I);
        $this->loginUser($I, $user);
        $I->amOnPage("/account/list");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');

        // No limit
        for ($i = 0; $i < 3; $i++) {
            $I->sendPOST('/account/updater/start', json_encode([
                'accounts' => [$accountId],
                'startKey' => rand(0, 10000000),
                'source' => 'one',
            ]));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
    }

    public function updateWithOneError(\TestSymfonyGuy $I)
    {
        $I->wantToTest('that failed updates would not count towards the account update limit');
        $accountId = $I->createAwAccount($this->user->getUserid(), 'testprovider', 'provider.error');
        $I->amOnPage("/account/list/");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');
        // One failed update
        $I->sendPOST('/account/updater/start', json_encode([
            'accounts' => [$accountId],
            'startKey' => rand(0, 10000000),
            'source' => 'one',
        ]));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='error')]");

        // And 2 normal updates
        $I->updateInDatabase('Account', ['Login' => 'future.reservation'], ['AccountID' => $accountId]);

        for ($i = 0; $i < 2; $i++) {
            $I->sendPOST('/account/updater/start', json_encode([
                'accounts' => [$accountId],
                'startKey' => rand(0, 10000000),
                'source' => 'one',
            ]));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
        // Third update - should fail now
        $I->sendPOST('/account/updater/start', json_encode([
            'accounts' => [$accountId],
            'startKey' => rand(0, 10000000),
            'source' => 'one',
        ]));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='fail')]");
    }

    public function updateImpersonated(\TestSymfonyGuy $I)
    {
        $I->wantToTest('that impersonated users are not constrained by account update limits');
        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $I->createAwAccount($userId, 'testprovider', 'future.reservation');
        $I->amOnPage("/manager/impersonate?UserID=$userId");
        $I->submitForm('form', []);
        $I->seeInDatabase('ImpersonateLog', ['UserID' => $this->user->getUserid(), 'TargetUserID' => $userId, 'IPAddress' => '127.0.0.1', 'UserAgent' => 'Symfony BrowserKit']);
        $I->amOnPage("/account/list/");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');

        for ($i = 0; $i < 3; $i++) {
            $I->sendPOST('/account/updater/start', json_encode([
                'accounts' => [$accountId],
                'startKey' => rand(0, 10000000),
                'source' => 'one',
            ]));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
    }

    public function updateOneFromTrips(\TestSymfonyGuy $I)
    {
        $I->wantToTest('that indirect account update from Trips+ will not count towards the account update limit');
        $accountId = $I->createAwAccount(
            $this->user->getUserid(),
            'testprovider',
            'future.reservation',
            null,
            ['UpdateDate' => null]
        );
        // Trip+ update
        $updateDate = $I->grabFromDatabase('Account', 'UpdateDate', ['AccountID' => $accountId]);
        $I->assertNull($updateDate);
        $I->sendPOST('/trips/update', ['accounts' => [$accountId]]);
        $I->seeResponseCodeIs(200);
        $updateDate = $I->grabFromDatabase('Account', 'UpdateDate', ['AccountID' => $accountId]);
        $I->assertEqualsWithDelta(new \DateTime(), new \DateTime($updateDate), 5, '');
        // Account updates
        $I->amOnPage("/account/list/");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');

        for ($i = 0; $i < 2; $i++) {
            $I->sendPOST('/account/updater/start', json_encode([
                'accounts' => [$accountId],
                'startKey' => rand(0, 10000000),
                'source' => 'one',
            ]));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
        // Third update - should fail now
        $I->sendPOST('/account/updater/start', json_encode([
            'accounts' => [$accountId],
            'startKey' => rand(0, 10000000),
            'source' => 'one',
        ]));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='fail')]");
    }
}
