<?php

use AwardWallet\Tests\Acceptance\_steps\UserSteps;
use Codeception\Module\Aw;

class ChatCest
{
    /**
     * @group booking
     * @group frontend-acceptance-off
     */
    public function chat(WebGuy $I, Codeception\Scenario $scenario)
    {
        $userSteps = new UserSteps($scenario);
        $userSteps->deleteIfExist(CommonUser::$user_email);
        $user = $I->createAwUser(CommonUser::$user_username, CommonUser::$user_password, [], true, true);

        $request = $I->createAbRequest(['BookerUserID' => Aw::BOOKER_ID, 'CameFrom' => Aw::CAME_FROM_BOOKER, 'UserID' => $user]);

        $I->amOnPage($I->grabService('router')->generate(AbRequestViewPage::$route,
            ['id' => $request, '_switch_user' => CommonUser::$user_username]));

        $I->see("Booking Request #$request");

        $booker = $I->haveFriend('booker');
        $booker->does(
            function (WebGuy $I) use ($request) {
                $I->amOnBusiness();
                $I->amOnPage($I->grabService('router')->generate(AbRequestViewPage::$route,
                    ['id' => $request, '_switch_user' => CommonUser::$booker_login]));

                $I->waitForElement('.online');
                $I->executeJS("CKEDITOR.instances['" . AbRequestViewPage::$selector_bookerPostInput . "'].setData('<p>First test message. awardwallet.com</p>');");
                $I->executeJS("$('" . AbRequestViewPage::$selector_userPostSendButton . "').click()");
                $I->waitAjax();
            }
        );
        $I->waitForElement('.online');
        $I->see('First test message. awardwallet.com');
        $I->fillField(AbRequestViewPage::$selector_userPostInput, 'Test message as User');
        $I->executeJS("$('" . AbRequestViewPage::$selector_userPostSendButton . "').click()");
        $I->waitAjax();

        $booker->does(function (WebGuy $I) {
            $I->see('Test message as User');
        });
    }
}
