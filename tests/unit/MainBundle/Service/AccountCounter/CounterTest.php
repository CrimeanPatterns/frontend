<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\AccountCounter;

use AwardWallet\MainBundle\Service\AccountCounter\Counter;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class CounterTest extends BaseUserTest
{
    private ?Counter $counter = null;

    public function _before()
    {
        parent::_before();

        $this->counter = $this->container->get(Counter::class);
    }

    public function _after()
    {
        $this->counter = null;

        parent::_after();
    }

    public function testUserWithoutAccounts()
    {
        $userId = $this->dbBuilder->makeUser(new User());
        $summary = $this->counter->calculate($userId);
        $this->assertEquals(0, $summary->getCount());
    }

    public function testUserWithOneAccount()
    {
        $this->dbBuilder->makeAccount(new Account($user = new User()));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(1, $summary->getCount());
    }

    public function testUserWithOneCoupon()
    {
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $user = new User()));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(1, $summary->getCount());
    }

    public function testUserWithOneAccountAndOneCoupon()
    {
        $this->dbBuilder->makeAccount(new Account($user = new User()));
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $user));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(2, $summary->getCount());
    }

    public function testFamilyMemberWithOneAccountAndOneCoupon()
    {
        $fm = UserAgent::familyMember($user = new User());
        $this->dbBuilder->makeAccount(new Account($fm));
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $fm));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(2, $summary->getCount($fm->getId()));
        $this->assertEquals(0, $summary->getCount(0));
    }

    public function testFamilyMemberWithMultipleCouponsAndUserWithOneCoupon()
    {
        $fm = UserAgent::familyMember($user = new User());
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $fm));
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $fm));
        $this->dbBuilder->makeProviderCoupon(new ProviderCoupon('Coupon', null, $user));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(2, $summary->getCount($fm->getId()));
        $this->assertEquals(1, $summary->getCount(0));
    }

    public function testUserWithMultipleAccountsAndFamilyMemberWithoutAccounts()
    {
        $this->dbBuilder->makeAccount(new Account($user = new User()));
        $this->dbBuilder->makeAccount(new Account($user));
        $this->dbBuilder->makeAccount(new Account($user));
        $this->dbBuilder->makeUserAgent($fm = UserAgent::familyMember($user));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(3, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($fm->getId()));
    }

    public function testUserWithoutAccountsAndConnectedUserWithoutAccounts()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent(new UserAgent($connectedUser, $user));
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(0, $summary->getCount());
        $this->assertEquals(0, $summary->getCount($ua->getId()));
    }

    public function testUserShareAccount()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent(new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount(
            (new Account($connectedUser))
                ->shareTo($ua)
        );
        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(1, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(1, $summary->getCount($ua->getId()));
    }

    public function testUserShareAccountAndCoupon()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount(
            (new Account($connectedUser))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $connectedUser))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account($user))
                ->shareTo($ua2)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(1, $summary->getCount(0));
        $this->assertEquals(2, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(2, $summary->getCount(0));
        $this->assertEquals(1, $summary->getCount($ua2->getId()));
    }

    public function testFamilyMemberShareAccounts()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeUserAgent($fm = UserAgent::familyMember($connectedUser));
        $this->dbBuilder->makeAccount(
            (new Account($fm))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $fm))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account($connectedUser))
                ->shareTo($ua)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(3, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(3, $summary->getCount());
        $this->assertEquals(1, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua2->getId()));
        $this->assertEquals(2, $summary->getCount($fm->getId()));
    }

    public function testAttachedCoupon()
    {
        $user = new User();
        $fm = UserAgent::familyMember($user);
        $this->dbBuilder->makeAccount($account = new Account($fm));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $fm))
                ->setAccount($account)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(1, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(1, $summary->getCount($fm->getId()));
    }

    public function testSharingAttachedCouponAndAccount()
    {
        $user = new User();
        $connectedUser = new User();
        $connectedFm = UserAgent::familyMember($connectedUser);
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount($account = (new Account($connectedUser))->shareTo($ua));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $connectedUser))
                ->setAccount($account)
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount($account2 = (new Account($connectedFm))->shareTo($ua));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test2', null, $connectedFm))
                ->setAccount($account2)
                ->shareTo($ua)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(2, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(1, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua2->getId()));
        $this->assertEquals(1, $summary->getCount($connectedFm->getId()));
    }

    public function testPartialSharingAccountWithAttachedCoupon()
    {
        $user = new User();
        $connectedUser = new User();
        $connectedFm = UserAgent::familyMember($connectedUser);
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount($account = new Account($connectedUser));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $connectedUser))
                ->setAccount($account)
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount($account2 = (new Account($connectedFm))->shareTo($ua));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test2', null, $connectedFm))
                ->setAccount($account2)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(2, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(1, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua2->getId()));
        $this->assertEquals(1, $summary->getCount($connectedFm->getId()));
    }

    public function testSharingWithUnapprovedUserAgent()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser, [
            'IsApproved' => 0,
        ]));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount((new Account($connectedUser))->shareTo($ua));
        $this->dbBuilder->makeProviderCoupon(
            (new ProviderCoupon('Test', null, $connectedUser))
                ->shareTo($ua)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(0, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(2, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua2->getId()));
    }

    public function testAccountAndProviderStates()
    {
        $user = new User();
        $connectedUser = new User();
        $this->dbBuilder->makeUserAgent($ua = new UserAgent($user, $connectedUser));
        $this->dbBuilder->makeUserAgent($ua2 = new UserAgent($connectedUser, $user));
        $this->dbBuilder->makeAccount(
            (new Account(
                $connectedUser,
                new Provider(null, ['State' => PROVIDER_DISABLED])
            ))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account(
                $connectedUser,
                new Provider(null, ['State' => PROVIDER_ENABLED])
            ))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account(
                $connectedUser,
                null,
                [],
                ['State' => ACCOUNT_DISABLED]
            ))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account(
                $connectedUser,
                null,
                [],
                ['State' => ACCOUNT_ENABLED]
            ))
                ->shareTo($ua)
        );
        $this->dbBuilder->makeAccount(
            (new Account(
                $connectedUser,
                null,
                [],
                ['State' => ACCOUNT_PENDING]
            ))
                ->shareTo($ua)
        );

        $summary = $this->counter->calculate($user->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(0, $summary->getCount(0));
        $this->assertEquals(2, $summary->getCount($ua->getId()));

        $summary = $this->counter->calculate($connectedUser->getId());
        $this->assertEquals(2, $summary->getCount());
        $this->assertEquals(2, $summary->getCount(0));
        $this->assertEquals(0, $summary->getCount($ua2->getId()));
    }
}
