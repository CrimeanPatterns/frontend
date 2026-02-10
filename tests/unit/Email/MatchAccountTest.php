<?php

namespace AwardWallet\Tests\Unit\Email;

use AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount;
use AwardWallet\MainBundle\Email\StatementMatcher;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class MatchAccountTest extends BaseUserTest
{
    /** @var StatementMatcher */
    private $matcher;

    public function _before()
    {
        parent::_before();
        $this->matcher = new StatementMatcher($this->em->getRepository(Account::class), new NullLogger());
    }

    public function testMatchByNumber()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        $ownerFam = new Owner($this->user, $fam);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Account[] $accs */
        $accs = [
            $this->createAcc('delta', '1122', ACCOUNT_PENDING, null),
            $this->createAcc('delta', '2233', ACCOUNT_PENDING, null),
            $this->createAcc('delta', '3344', ACCOUNT_PENDING, $famId),
            $this->createAcc('delta', '4455', ACCOUNT_PENDING, $famId),
        ];
        $this->aw->createAccountProperty('Number', 'aabb', ['AccountID' => $accs[0]->getId()], $accs[0]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'bbcc', ['AccountID' => $accs[1]->getId()], $accs[1]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'ccdd', ['AccountID' => $accs[2]->getId()], $accs[2]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'ddee', ['AccountID' => $accs[3]->getId()], $accs[3]->getProviderid()->getProviderid());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('1122', null, null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[0]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData('33', 'left', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[1]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('33', 'right', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[2]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData('4**5', 'center', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[3]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, 'ddee', null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[3]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData(null, null, 'dd', 'left'));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[2]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, 'bb', 'right'));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[1]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData(null, null, 'a**b', 'center'));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[0]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, '44', 'left'));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[2]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData('b**c', 'center', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[1]->getId(), $rep->acc->getId());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('login', null, null, null));
        $this->assertNull($rep->acc);

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData(null, null, 'number', null));
        $this->assertNull($rep->acc);

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('11', 'left', null, null));
        $this->assertNull($rep->acc);

        $rep = $this->matcher->match($ownerFam, $delta, $this->getLoyaltyData(null, null, 'ee', 'right'));
        $this->assertNull($rep->acc);
    }

    public function testMatchSingleOwned()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        $ownerFam = new Owner($this->user, $fam);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Provider $united */
        $united = $this->em->getRepository(Provider::class)->findOneByCode('mileageplus');
        /** @var Account[] $accs */
        $accs = [
            $this->createAcc('delta', '1122', ACCOUNT_ENABLED, null),
            $this->createAcc('mileageplus', '4455', ACCOUNT_ENABLED, null),
            $this->createAcc('mileageplus', '6677', ACCOUNT_ENABLED, $famId),
        ];

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[0]->getId(), $rep->acc->getId());
        $this->assertEquals(1, $rep->cnt);

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('login', null, null, null));
        $this->assertNull($rep->acc);

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, 'nummber', null));
        $this->assertNull($rep->acc);

        $rep = $this->matcher->match($ownerFam, $united, $this->getLoyaltyData(null, null, null, null));
        $this->assertNull($rep->acc);
    }

    public function testMatchSingleDiscovered()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        $ownerFam = new Owner($this->user, $fam);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Provider $united */
        $united = $this->em->getRepository(Provider::class)->findOneByCode('mileageplus');
        /** @var Account[] $accs */
        $accs = [
            $this->createAcc('delta', '1122', ACCOUNT_ENABLED, null),
            $this->createAcc('delta', '2233', ACCOUNT_PENDING, null),
            $this->createAcc('delta', '3344', ACCOUNT_ENABLED, null),
            $this->createAcc('delta', '4455', ACCOUNT_PENDING, $famId),
            $this->createAcc('mileageplus', '6677', ACCOUNT_PENDING, null),
            $this->createAcc('mileageplus', '7788', ACCOUNT_ENABLED, $famId),
            $this->createAcc('mileageplus', '8899', ACCOUNT_ENABLED, $famId),
            $this->createAcc('mileageplus', '9900', ACCOUNT_PENDING, $famId),
        ];
        $this->aw->createAccountProperty('Number', 'aabb', ['AccountID' => $accs[0]->getId()], $accs[0]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'bbcc', ['AccountID' => $accs[2]->getId()], $accs[2]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'ccdd', ['AccountID' => $accs[5]->getId()], $accs[5]->getProviderid()->getProviderid());
        $this->aw->createAccountProperty('Number', 'ddee', ['AccountID' => $accs[6]->getId()], $accs[6]->getProviderid()->getProviderid());

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, 'mismatch', null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[1]->getId(), $rep->acc->getId());
        $this->assertEquals(4, $rep->cnt);

        $rep = $this->matcher->match($ownerFam, $united, $this->getLoyaltyData(null, null, 'mismatch', null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[7]->getId(), $rep->acc->getId());
        $this->assertEquals(4, $rep->cnt);
    }

    public function testNewEmpty()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        $ownerFam = new Owner($this->user, $fam);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Provider $united */
        $united = $this->em->getRepository(Provider::class)->findOneByCode('mileageplus');
        /** @var Account[] $accs */
        $accs = [
            $this->createAcc('delta', '', ACCOUNT_PENDING, null),
            $this->createAcc('mileageplus', '', ACCOUNT_PENDING, $famId),
        ];

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('login', null, null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[0]->getId(), $rep->acc->getId());
        $this->assertEquals(1, $rep->cnt);

        $rep = $this->matcher->match($ownerFam, $united, $this->getLoyaltyData(null, null, 'number', null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[1]->getId(), $rep->acc->getId());
        $this->assertEquals(1, $rep->cnt);
    }

    public function testMiss()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        $ownerFam = new Owner($this->user, $fam);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Provider $united */
        $united = $this->em->getRepository(Provider::class)->findOneByCode('mileageplus');
        /** @var Account[] $accs */
        $this->createAcc('delta', '1122', ACCOUNT_PENDING, null);
        $this->createAcc('delta', '2233', ACCOUNT_ENABLED, null);
        $this->createAcc('delta', '3344', ACCOUNT_PENDING, $famId);
        $this->createAcc('mileageplus', '8899', ACCOUNT_PENDING, $famId);
        $this->createAcc('mileageplus', '9900', ACCOUNT_ENABLED, $famId);
        $this->createAcc('mileageplus', '0011', ACCOUNT_PENDING, null);

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData(null, null, null, null));
        $this->assertNull($rep->acc);
        $this->assertEquals(3, $rep->cnt);

        $rep = $this->matcher->match($ownerFam, $united, $this->getLoyaltyData(null, null, null, null));
        $this->assertNull($rep->acc);
        $this->assertEquals(3, $rep->cnt);
    }

    public function testIgnored()
    {
        $famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
        /** @var Useragent $fam */
        $fam = $this->em->getRepository(Useragent::class)->find($famId);
        $ownerUsr = new Owner($this->user, null);
        /** @var Provider $delta */
        $delta = $this->em->getRepository(Provider::class)->findOneByCode('delta');
        /** @var Provider $united */
        $united = $this->em->getRepository(Provider::class)->findOneByCode('mileageplus');
        /** @var Provider $sw */
        $sw = $this->em->getRepository(Provider::class)->findOneByCode('rapidrewards');
        /** @var Account[] $accs */
        $accs = [
            $this->createAcc('delta', '1122', ACCOUNT_ENABLED, null),
            $this->createAcc('delta', '**22', ACCOUNT_IGNORED, null),

            $this->createAcc('mileageplus', '3344', ACCOUNT_ENABLED, $famId),
            $this->createAcc('mileageplus', '33**', ACCOUNT_IGNORED, null),

            $this->createAcc('rapidrewards', '**77', ACCOUNT_IGNORED, null),
        ];

        $rep = $this->matcher->match($ownerUsr, $delta, $this->getLoyaltyData('22', 'left', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[0]->getId(), $rep->acc->getId());
        $this->assertEquals(2, $rep->cnt);

        $rep = $this->matcher->match($ownerUsr, $united, $this->getLoyaltyData('33', 'right', null, null));
        $this->assertNotNull($rep->acc);
        $this->assertEquals($accs[2]->getId(), $rep->acc->getId());
        $this->assertEquals(2, $rep->cnt);

        $rep = $this->matcher->match($ownerUsr, $sw, $this->getLoyaltyData('77', 'left', null, null));
        $this->assertNull($rep->acc);
        $this->assertEquals(1, $rep->cnt);
    }

    private function createAcc($code, $login, $state, $userAgentId): Account
    {
        $accId = $this->aw->createAwAccount($this->user->getId(), $code, $login, '', ['State' => $state, 'UserAgentID' => $userAgentId]);

        return $this->em->getRepository(Account::class)->find($accId);
    }

    private function getLoyaltyData($login, $loginMask, $number, $numberMask): LoyaltyAccount
    {
        $data = new LoyaltyAccount();
        $data->login = $login;
        $data->loginMask = $loginMask;
        $data->number = $number;
        $data->numberMask = $numberMask;

        return $data;
    }
}
