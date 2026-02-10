<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\Notification\Unsubscriber;
use Codeception\Example;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ProfileNotificationsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @var RouterInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
    }

    public function personalSettings(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        /** @var Usr $user */
        $user = $em->find(Usr::class, $userId);
        $email = $user->getEmail();
        $I->amOnPage($this->router->generate("aw_profile_notifications", ["_switch_user" => $user->getLogin()]));
        $I->seeInTitle("Notifications");
        $I->see("Disable All Notifications");
        $I->see("Mobile");
        $I->see("Desktop");
        $I->see("Email");

        $I->dontSeeInDatabase('DoNotSend', ['Email' => $email]);

        $this->checkSettings($I, [
            'DisableAll' => [false, false, false],
            'Expire' => [true, true, '90, 60, 30, and every day for the last 7 days before expiration'],
            'RewardsActivity' => [true, true, 'Daily'],
            'NewPlans' => [true, true, true],
            'PlanChanges' => [true, true, true],
            'Checkins' => [true, true, true],
            'ProductUpdates' => [true, true, true],
            'Offers' => [true, true, true],
            'NewBlogPosts' => [false, false, 'Weekly'],
            'InviteeReg' => [null, null, true],
            //            'Connected' => [false, false, false],
            'NotConnected' => [true, true, true],
        ]);

        // uncheck emailnewplans
        $I->uncheckOption('[name="desktop_notification[emailNewPlans]"]');
        $I->uncheckOption('[name="desktop_notification[wpNewPlans]"]');
        $I->checkOption('[name="desktop_notification[emailDisableAll]"]');

        $this->mockBackgroundCheckScheduler($I, [
            'onUserNotificationPreferencesUpdated' => Stub::once(),
        ]);

        $I->click('Update');
        $I->seeInDatabase('DoNotSend', ['Email' => $email]);
        $I->seeInDatabase('Usr', [
            'Login' => $user->getLogin(),
            'EmailNewPlans' => 0,
            'WpNewPlans' => 0,
        ]);
    }

    /**
     * @dataProvider checkLimitedAccessDataProvider
     */
    public function checkLimitedAccess(\TestSymfonyGuy $I, Example $example)
    {
        $updates = [
            'AccountLevel' => $example['AccountLevel'],
            'IsUs' => $example['IsUs'] ?? 1,
        ];

        $I->mockService(GeoLocation::class, $I->stubMake(GeoLocation::class, [
            'getCountryIdByIp' => $example['IsUs'] ?? true ? Country::UNITED_STATES : 0,
        ]));

        if ($example['UnsubscribedFromAllEmailsOptions']) {
            $updates = array_merge($updates, [
                'EmailExpiration' => 0,
                'EmailRewards' => REWARDS_NOTIFICATION_NEVER,
                'EmailNewPlans' => 0,
                'EmailPlansChanges' => 0,
                'CheckinReminder' => 0,
                'EmailProductUpdates' => 0,
                'EmailOffers' => 0,
                'EmailNewBlogPosts' => 0,
                'EmailInviteeReg' => 0,
                'EmailFamilyMemberAlert' => 0,
            ]);
        }

        $userId = $I->createAwUser(null, null, $updates);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        /** @var Usr $user */
        $user = $em->find(Usr::class, $userId);

        if ($example['GiveTrial']) {
            /** @var EntityManagerInterface $em */
            $em = $I->grabService(EntityManagerInterface::class);
            $em->refresh($user);
            /** @var Manager $cartManager */
            $cartManager = $I->grabService(Manager::class);
            $cartManager->setUser($user);
            $cartManager->createNewCart();
            $cartManager->giveAwPlusTrial();
            $cartManager->markAsPayed();
            $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user->getAccountLevel());
        }

        $I->amOnPage($this->router->generate("aw_profile_notifications", ["_switch_user" => $user->getLogin()]));

        $this->checkSettings($I, [
            'DisableAll' => [false, false, false],
            'Expire' => [true, true, $example['expectedInputChecked'] ? '90, 60, 30, and every day for the last 7 days before expiration' : 'Never'],
            'RewardsActivity' => [true, true, $example['expectedInputChecked'] ? 'Daily' : 'Never'],
            'NewPlans' => [true, true, $example['expectedInputChecked']],
            'PlanChanges' => [true, true, $example['expectedInputChecked']],
            'Checkins' => [true, true, $example['expectedInputChecked']],
            'ProductUpdates' => [true, true, $example['expectedInputChecked']],
            'Offers' => [true, true, $example['expectedInputChecked']],
            'NewBlogPosts' => [false, false, false],
            'InviteeReg' => [null, null, $example['expectedInputChecked']],
            //            'Connected' => [false, false, false],
            'NotConnected' => [true, true, $example['expectedInputChecked']],
        ]);

        $I->seeElement('#desktop_notification_emailExpire:enabled');
        $I->seeElement('#desktop_notification_emailRewardsActivity:enabled');
        $I->seeElement('#desktop_notification_emailNewPlans:enabled');
        $I->seeElement('#desktop_notification_emailPlanChanges:enabled');
        $I->seeElement('#desktop_notification_emailCheckins:enabled');
        $I->seeElement('#desktop_notification_emailProductUpdates:enabled');
        $I->seeElement('#desktop_notification_emailOffers:' . $example['expectedInputState']);
        $I->seeElement('#desktop_notification_emailNewBlogPosts:enabled');
        $I->seeElement('#desktop_notification_emailInviteeReg:enabled');
        $I->seeElement('#desktop_notification_emailNotConnected:enabled');

        // uncheck emailnewplans
        $I->uncheckOption('[name="desktop_notification[emailNewPlans]"]');

        $this->mockBackgroundCheckScheduler($I, [
            'onUserNotificationPreferencesUpdated' => $example['UnsubscribedFromAllEmailsOptions'] ? Stub::never() : Stub::once(),
        ]);

        $I->click('Update');
        $I->seeInDatabase('Usr', [
            'Login' => $user->getLogin(),
            'EmailNewPlans' => $example['expectedEmailNewPlans'],
        ]);
    }

    public function unsubscribeDevice(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $deviceId = $I->haveInDatabase("MobileDevice", [
            'DeviceKey' => StringUtils::getRandomCode(200),
            'DeviceType' => MobileDevice::TYPE_CHROME,
            'Lang' => 'en',
            'UserID' => $userId,
        ]);
        $device = $I->getContainer()->get("doctrine.orm.entity_manager")->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)->find($deviceId);
        $unsubscriber = $I->getContainer()->get(Unsubscriber::class);

        $I->amOnPage($this->router->generate("aw_home") . "?_switch_user=_exit");
        $I->amOnPage($this->router->generate("aw_profile_notifications") . "?_switch_user=" . $username);

        $I->saveCsrfToken();

        $I->sendPOST($this->router->generate("aw_mobile_device_unsubscribe", ["code" => $unsubscriber->getUnsubscribeCode($device)]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "unsubscribed"]);

        $I->dontSeeInDatabase("MobileDevice", ["MobileDeviceID" => $deviceId]);
    }

    public function sendTestPush(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $deviceId = $I->haveInDatabase("MobileDevice", [
            'DeviceKey' => StringUtils::getRandomCode(200),
            'DeviceType' => MobileDevice::TYPE_CHROME,
            'Lang' => 'en',
            'UserID' => $userId,
        ]);
        $device = $I->getContainer()->get("doctrine.orm.entity_manager")->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)->find($deviceId);
        $unsubscriber = $I->getContainer()->get(Unsubscriber::class);

        $I->amOnPage($this->router->generate("aw_home") . "?_switch_user=_exit");
        $I->amOnPage($this->router->generate("aw_profile_notifications") . "?_switch_user=" . $username);

        $I->saveCsrfToken();

        $I->sendPOST($this->router->generate("aw_device_send_push", ["code" => $unsubscriber->getUnsubscribeCode($device)]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["sent" => true]);
    }

    public function setAlias(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $deviceId = $I->haveInDatabase("MobileDevice", [
            'DeviceKey' => StringUtils::getRandomCode(200),
            'DeviceType' => MobileDevice::TYPE_CHROME,
            'Lang' => 'en',
            'UserID' => $userId,
        ]);
        $device = $I->getContainer()->get("doctrine.orm.entity_manager")->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)->find($deviceId);
        $unsubscriber = $I->getContainer()->get(Unsubscriber::class);

        $I->amOnPage($this->router->generate("aw_home") . "?_switch_user=_exit");
        $I->amOnPage($this->router->generate("aw_profile_notifications") . "?_switch_user=" . $username);

        $I->saveCsrfToken();

        $alias = StringUtils::getRandomCode(20);

        $I->sendPOST(
            $this->router->generate("aw_device_set_alias", ["code" => $unsubscriber->getUnsubscribeCode($device)]),
            [
                'alias' => $alias,
            ]
        );
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "success"]);

        $I->seeInDatabase("MobileDevice", ["MobileDeviceID" => $deviceId, "Alias" => $alias]);
    }

    private function checkLimitedAccessDataProvider(): array
    {
        return [
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'GiveTrial' => false,
                'UnsubscribedFromAllEmailsOptions' => false,
                'IsUs' => 1,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'disabled',
                'expectedInputChecked' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'GiveTrial' => false,
                'UnsubscribedFromAllEmailsOptions' => false,
                'IsUs' => 0,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'enabled',
                'expectedInputChecked' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'GiveTrial' => false,
                'UnsubscribedFromAllEmailsOptions' => true,
                'IsUs' => 1,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'disabled',
                'expectedInputChecked' => false,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'GiveTrial' => false,
                'UnsubscribedFromAllEmailsOptions' => true,
                'IsUs' => 0,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'enabled',
                'expectedInputChecked' => false,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'GiveTrial' => false,
                'UnsubscribedFromAllEmailsOptions' => false,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'enabled',
                'expectedInputChecked' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'GiveTrial' => true,
                'UnsubscribedFromAllEmailsOptions' => false,
                'expectedEmailNewPlans' => 0,
                'expectedInputState' => 'disabled',
                'expectedInputChecked' => true,
            ],
        ];
    }

    private function checkSettings(\TestSymfonyGuy $I, array $names)
    {
        foreach ($names as $baseName => $values) {
            foreach (['mp', 'wp', 'email'] as $k => $pref) {
                $name = $pref . $baseName;
                $value = $values[$k];

                if (is_bool($value)) {
                    if ($value) {
                        $I->seeCheckboxIsChecked('[name="desktop_notification[' . $name . ']"]');
                    } else {
                        $I->dontSeeCheckboxIsChecked('[name="desktop_notification[' . $name . ']"]');
                    }
                } elseif (is_null($value)) {
                    continue;
                } else {
                    $I->seeOptionIsSelected('[name="desktop_notification[' . $name . ']"]', $value);
                }
            }
        }
    }

    private function mockBackgroundCheckScheduler(\TestSymfonyGuy $I, array $params)
    {
        $I->mockService(BackgroundCheckScheduler::class, $I->stubMake(BackgroundCheckScheduler::class, $params));
    }
}
