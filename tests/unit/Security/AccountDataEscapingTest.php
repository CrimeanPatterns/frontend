<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group security
 * @group frontend-unit
 */
class AccountDataEscapingTest extends BaseUserTest
{
    public function testMobile()
    {
        $couponId = $this->aw->createAwCoupon(
            $userId = $this->user->getUserid(),
            '&lt;svg/onload=prompt(1);&gt;',
            '&lt;svg/onload=prompt(1);&gt;',
            '&lt;svg/onload=prompt(1);&gt;'
        );
        $customAccountId = $this->aw->createAwAccount($userId, null, '<svg/onload=prompt(1);>', '', [
            'Comment' => '<svg/onload=prompt(1);>',
            'ProgramName' => '&lt;svg/onload=prompt(1);&gt;',
            'LoginURL' => 'javascript:alert(11)',
        ]);

        $listOptions = $this->container->get(OptionsFactory::class)
            ->createMobileOptions()
            ->set(Options::OPTION_USER, $this->user);
        $accounts = $this->container->get(AccountListManager::class)->getAccountList($listOptions)->getAccounts();
        $coupon = $accounts['c' . $couponId];

        $this->assertArrayContainsArray([
            'Login' => '123456789',
            'Balance' => '&lt;svg/onload=prompt(1);&gt;',
            'Description' => '&lt;svg/onload=prompt(1);&gt;',
            'DisplayName' => '&lt;svg/onload=prompt(1);&gt;',
        ],
            $coupon
        );

        // TODO: extract AccountSharingTest::assertBlocks, AccountSharingTest::findArrayByCriteria methods to helper\trait\module
        $this->assertEquals([
            [
                'Kind' => 'balance',
                'Name' => 'Coupon Value',
                'Val' => [
                    'Balance' => '&lt;svg/onload=prompt(1);&gt;',
                ],
            ],
            [
                'Kind' => 'string',
                'Name' => 'Note',
                'Val' => '&lt;svg/onload=prompt(1);&gt;',
            ],
        ], array_merge(
            array_slice($coupon['Blocks'], 3, 1),
            array_slice($coupon['Blocks'], 5, 1)
        )
        );

        $customAccount = $accounts['a' . $customAccountId];

        $this->assertArrayContainsArray([
            'Login' => '<svg/onload=prompt(1);>',
            'Balance' => 'n/a',
            'DisplayName' => '&lt;svg/onload=prompt(1);&gt;',
            'Autologin' => [
                'loginUrl' => 'http://javascript:alert(11)',
            ],
        ],
            $customAccount
        );

        $this->assertEquals(
            [
                [
                    'Kind' => 'login',
                    'Name' => 'Login',
                    'Val' => '<svg/onload=prompt(1);>',
                ],
                [
                    'Kind' => 'string',
                    'Name' => 'Comment',
                    'Val' => '<svg/onload=prompt(1);>',
                ],
            ],
            array_slice($customAccount['Blocks'], 3, 2)
        );
    }
}
