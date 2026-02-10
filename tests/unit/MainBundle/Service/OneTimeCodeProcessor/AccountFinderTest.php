<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\AccountFinder;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\OtcCache;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Stub;

/**
 * @group frontend-unit
 */
class AccountFinderTest extends BaseContainerTest
{
    private $user;

    public function _before()
    {
        parent::_before();
        $this->user = $this->em->getRepository(Usr::class)->find($this->aw->createAwUser());
    }

    public function _after()
    {
        parent::_after();
        $this->user = null;
    }

    public function testFindExpecting()
    {
        $acc1 = $this->aw->createAwAccount($this->user->getId(), 'klbbluebiz', 'login1', '', ['ErrorCode' => ACCOUNT_QUESTION, 'Question' => 'We’ve sent the PIN code to your e-mail']);
        $acc2 = $this->aw->createAwAccount($this->user->getId(), 'klbbluebiz', 'login2', '', ['ErrorCode' => ACCOUNT_QUESTION, 'Question' => 'We’ve sent the PIN code to your e-mail']);
        $time = time();
        $cache = $this->makeEmpty(OtcCache::class, [
            'getCheck' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => $time - 10, $acc2 => null][$key];
            }),
            'getUpdate' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => $time - 5, $acc2 => null][$key];
            }),
        ]);
        $finder = new AccountFinder(
            $cache,
            $this->em->getRepository(Account::class)
        );
        $r = $finder->find($this->user, 'klm');
        $this->assertEquals(1, count($r->candidates));
        $this->assertEquals($acc1, $r->found->getId());
    }

    public function testFindPending()
    {
        $acc1 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login1', '', ['ErrorCode' => ACCOUNT_QUESTION, 'Question' => 'Enter code that was sent to email']);
        $acc2 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login2');
        $time = time();
        $cache = $this->makeEmpty(OtcCache::class, [
            'getCheck' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => null, $acc2 => $time - 5][$key];
            }),
            'getUpdate' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => null, $acc2 => $time - 50][$key];
            }),
        ]);
        $finder = new AccountFinder(
            $cache,
            $this->em->getRepository(Account::class),
            $this->em->getRepository(Provider::class));
        $r = $finder->find($this->user, 'testprovider');
        $this->assertEquals(1, count($r->candidates));
        $this->assertnull($r->found);
    }

    public function testEmpty()
    {
        $acc1 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login1', '', ['ErrorCode' => ACCOUNT_QUESTION, 'Question' => 'Enter code that was sent to email']);
        $acc2 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login2');
        $time = time();
        $cache = $this->makeEmpty(OtcCache::class, [
            'getCheck' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => null, $acc2 => $time - 55][$key];
            }),
            'getUpdate' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => null, $acc2 => $time - 5][$key];
            }),
        ]);
        $finder = new AccountFinder(
            $cache,
            $this->em->getRepository(Account::class),
            $this->em->getRepository(Provider::class));
        $r = $finder->find($this->user, 'testprovider');
        $this->assertEquals(0, count($r->candidates));
        $this->assertNull($r->found);
    }

    public function testAmbiguous()
    {
        $acc1 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login1', '', ['ErrorCode' => ACCOUNT_QUESTION, 'Question' => 'Enter code that was sent to email']);
        $acc2 = $this->aw->createAwAccount($this->user->getId(), 'testprovider', 'login2');
        $time = time();
        $cache = $this->makeEmpty(OtcCache::class, [
            'getCheck' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => $time - 20, $acc2 => $time - 5][$key];
            }),
            'getUpdate' => Stub\Expected::exactly(2, function ($key) use ($acc1, $acc2, $time) {
                return [$acc1 => $time - 10, $acc2 => null][$key];
            }),
        ]);
        $finder = new AccountFinder(
            $cache,
            $this->em->getRepository(Account::class),
            $this->em->getRepository(Provider::class));
        $r = $finder->find($this->user, 'testprovider');
        $this->assertEquals(2, count($r->candidates));
    }
}
