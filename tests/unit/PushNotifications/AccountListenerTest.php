<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Event\AccountExpiredEvent;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Modules\DbBuilder\Currency;

/**
 * @group frontend-unit
 */
class AccountListenerTest extends BaseNotificationListenerTest
{
    public function testSubaccountWithCustomFormatBalance()
    {
        $this->getDevice('3.8.11');
        $this->aw->createAwProvider(
            $code = 'TestAccount' . StringUtils::getRandomCode(7),
            $code,
            [
                'ShortName' => 'TestAccount',
                'Currency' => 2,
                'BalanceFormat' => 'function',
            ],
            ['Parse' => function () {
                /** @var $this \TAccountChecker */
                $this->SetBalance(100);
                $this->SetProperty('SubAccounts', [[
                    "Code" => 'tescoSaving',
                    "DisplayName" => "Clubcard Fuel Save",
                    "Balance" => '5',
                    'Kind' => 'C',
                    'ExpirationDate' => (new \DateTime('+30 days 12:00'))->getTimestamp(),
                ]]);
            }],
            ['FormatBalance' => function ($fields, $properties) {
                return '&pound;' . $fields['Balance'];
            }]
        );

        $notifications = $this->captureNotificationsOn(function () use ($code) {
            $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
            $this->aw->checkAccount($accountId);
            $subAccountId = $this->db->grabFromDatabase('SubAccount', 'SubAccountID', ['AccountID' => $accountId]);
            $event = new AccountExpiredEvent([Subaccount::class, $subAccountId], $this->user->getId());
            $this->container->get('aw.push_notification.account.listener')->onAccountExpire($event);
        });

        $this->assertOneNotificationWithText($notifications, '/TestAccount Clubcard Fuel Save - £5 expiring in 1 month/ms');
    }

    public function testFICOSubaccount()
    {
        $count = 1;
        $manager = $this->container->get('aw.manager.mobile_device_manager');
        $manager->addDevice(
            $this->user->getId(),
            MobileDevice::getTypeName(MobileDevice::TYPE_IOS),
            'keykey',
            'en',
            '1.1.1',
            '127.0.0.1'
        );

        $this->aw->createAwProvider(
            $code = 'someFICO' . StringUtils::getRandomCode(7),
            $code,
            ['ShortName' => 'Some short name'],
            ['Parse' => function () use (&$count) {
                /** @var $this \TAccountChecker */
                $this->SetBalance(100500);
                $this->SetProperty('SubAccounts', [
                    [
                        "Code" => 'somebankFICO',
                        "DisplayName" => "Some Bank FICO Score",
                        "Balance" => $count++ === 1 ? 800 : 820,
                    ],
                    [
                        "Code" => 'somesubacccode',
                        'DisplayName' => 'some subacc name',
                        'Balance' => 100,
                    ],
                ]);
            }]
        );

        $notifications = $this->captureNotificationsOn(function () use ($code) {
            $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
            $this->aw->checkAccount($accountId);
            $this->aw->checkAccount($accountId);
        });

        $this->assertOneNotificationWithText($notifications, null, '/Credit Score Update/ms');
    }

    public function testStatusChange()
    {
        // logout
        $this->container->get("aw.security.token_storage")->setToken(null);

        $count = 0;
        $manager = $this->container->get('aw.manager.mobile_device_manager');
        $manager->addDevice(
            $this->user->getId(),
            MobileDevice::getTypeName(MobileDevice::TYPE_IOS),
            'keykey',
            'en',
            '1.1.1',
            '127.0.0.1'
        );

        $providerId = $this->aw->createAwProvider(
            $code = 'test' . StringUtils::getRandomCode(7),
            $code,
            ['ShortName' => 'Some short name'],
            ['Parse' => function () use (&$count) {
                /** @var $this \TAccountChecker */
                $this->SetBalance(100500);
                $status = 'Luxury Premium Deluxe ' . ++$count;
                $this->SetProperty('Status', $status);
            }]
        );

        $this->db->haveInDatabase('ProviderProperty', [
            'ProviderID' => $providerId,
            'Name' => 'Level',
            'Code' => 'Status',
            'SortIndex' => 20,
            'Required' => 1,
            'Kind' => 3,
            'Visible' => 1,
        ]);

        $notifications = $this->captureNotificationsOn(function () use ($code) {
            $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
            $this->aw->checkAccount($accountId);
            $this->aw->checkAccount($accountId);
            $this->aw->checkAccount($accountId);
        });

        $this->assertOneNotificationWithText($notifications, null, '/Some short name status change/');
    }

    public function testRewardsActivityWithNonLocalizedCurrency()
    {
        $manager = $this->container->get('aw.manager.mobile_device_manager');
        $manager->addDevice(
            $this->user->getId(),
            MobileDevice::getTypeName(MobileDevice::TYPE_IOS),
            'keykey',
            'en',
            '1.1.1',
            '127.0.0.1'
        );

        $currencyId = $this->dbBuilder->makeCurrency(new Currency('Test Currency', '*', 'TST'));
        $checkCount = 0;
        $this->aw->createAwProvider(
            $code = 'ra' . StringUtils::getRandomCode(7),
            $code,
            ['ShortName' => 'Some short name', 'Currency' => $currencyId],
            ['Parse' => function () use (&$checkCount) {
                /** @var $this \TAccountChecker */
                $this->SetBalance(100500 + ($checkCount += 100));
            }]
        );

        $notifications = $this->captureNotificationsOn(function () use ($code) {
            $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
            $this->aw->checkAccount($accountId);
            $this->aw->checkAccount($accountId);
        });

        $this->assertOneNotificationWithText($notifications, '/^Some short name: \+100 \(from/ims');
    }

    public function testRewardsActivityWithLocalizedCurrency()
    {
        $manager = $this->container->get('aw.manager.mobile_device_manager');
        $manager->addDevice(
            $this->user->getId(),
            MobileDevice::getTypeName(MobileDevice::TYPE_IOS),
            'keykey',
            'en',
            '1.1.1',
            '127.0.0.1'
        );

        $checkCount = 0;
        $this->aw->createAwProvider(
            $code = 'ra' . StringUtils::getRandomCode(7),
            $code,
            ['ShortName' => 'Some short name', 'Currency' => 1],
            ['Parse' => function () use (&$checkCount) {
                /** @var $this \TAccountChecker */
                $this->SetBalance(100500 + ($checkCount += 100));
            }]
        );

        $notifications = $this->captureNotificationsOn(function () use ($code) {
            $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
            $this->aw->checkAccount($accountId);
            $this->aw->checkAccount($accountId);
        });

        $this->assertOneNotificationWithText($notifications, '/^Some short name: \+100 miles \(from/ims');
    }
}
